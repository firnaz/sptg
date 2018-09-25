<?php
class PyCswClient extends Zend_Controller_Action_Helper_Abstract {
	private $url = "";
	private $sipitung;

	public function __construct($url){
		$this->url = $url;
		$this->sipitung = Zend_Controller_Action_HelperBroker::getStaticHelper('Sipitung');
	}

	public function getRecords($filter="", $limit=10, $start=0, $sort=""){
		$cswlayer = new Cswlayer();
		if(!$filter){
			$filter = '<ogc:Filter>
						<ogc:PropertyIsLike escapeChar="\" singleChar="?" wildCard="*">
							<ogc:PropertyName>dc:identifier</ogc:PropertyName>
							<ogc:Literal>*</ogc:Literal>
				        </ogc:PropertyIsLike>
				      </ogc:Filter>'; 
		}
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<GetRecords service="CSW" version="2.0.2"
				  maxRecords="'.$limit.'"
				  startPosition="'.($start+1).'"
				  resultType="results"
				  outputSchema="http://www.opengis.net/cat/csw/2.0.2"
				  xmlns="http://www.opengis.net/cat/csw/2.0.2"
				  xmlns:csw="http://www.opengis.net/cat/csw/2.0.2"
				  xmlns:ogc="http://www.opengis.net/ogc"
				  xmlns:ows="http://www.opengis.net/ows"
				  xmlns:dc="http://purl.org/dc/elements/1.1/"
				  xmlns:dct="http://purl.org/dc/terms/"
				  xmlns:gml="http://www.opengis.net/gml"
				  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				  xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2/../../../csw/2.0.2/CSW-discovery.xsd">
				  <Query typeNames="csw:Record">
				     <ElementSetName typeNames="csw:Record">full</ElementSetName>
				     <Constraint version="1.1.0">
				     '.$filter.'
				     </Constraint>
				     '.$sort.'
				  </Query>
				</GetRecords>';
		// echo $xml;exit;
		$client = new Zend_Http_Client($this->url);
		$client->setConfig(array('timeout'=>30));
		$response = $client->setRawData($xml, 'application/xml')->request('POST');
		$result = $this->getRecordsResponseToArray($response->getBody());
		// print_r($result);exit;

		$searchResult = $result["csw:SearchResults"]["csw:Record"];
		$total = $result["csw:SearchResults_attr"]["numberOfRecordsMatched"];
		if($total){
			foreach($searchResult as $key=>$val){
				if(!is_numeric($key)){
					continue;
				}
				$identifier = $val["dc:identifier"];
				$data[$key]=array(
						"identifier" =>$identifier,
						"title"      =>$val["dc:title"],
						"modified"   =>$val["dct:modified"],
						"abstract"   =>$val["dct:abstract"],
						"type"       =>(is_array($val["dc:type"])?implode(",",$val["dc:type"]):$val["dc:type"]),
						"subject"    =>(is_array($val["dc:subject"])?implode(",",$val["dc:subject"]):$val["dc:subject"]),
						"format"     =>$val["dc:format"],
						"creator"    =>$val["dc:creator"],
						"contributor"=>$val["dc:contributor"],
						"language"   =>$val["dc:language"]
					);
					$BoundingBox = $val["ows:BoundingBox"];
					// print_r($BoundingBox);exit;
				if($BoundingBox){
					$lowercorner = explode(" ",$BoundingBox["ows:LowerCorner"]);
					$uppercorner = explode(" ",$BoundingBox["ows:UpperCorner"]);
					$srs = $val["ows:BoundingBox_attr"]["crs"];
				}
				$data[$key]["srs"] = $srs;
				$data[$key]["xmin"] = $lowercorner[0];
				$data[$key]["ymin"] = $lowercorner[1];
				$data[$key]["xmax"] = $uppercorner[0];
				$data[$key]["ymax"] = $uppercorner[1];
				$data[$key]["boundingbox"] = $lowercorner[0]." ".$lowercorner[1].", ".$uppercorner[0]." ".$uppercorner[1];

				$conf = Zend_Registry::get('config');
				$row = $cswlayer->fetchRow("identifier='$identifier'");
				// $wms = array("url"=>$conf["geoserver.url"], "layer"=>$conf["geoserver.workspace"].":".$row->layer, "tipe_layer"=>$row->tipe_layer);
				$data[$key]["url"] = $conf["geoserver.url"]."/wms";
				$data[$key]["layer"] = $conf["geoserver.workspace"].":".$row->layer;
				$data[$key]["thumbnail"] = $conf["geoserver.workspace"].":".$row->layer.".png";
				$data[$key]["num_view"] = ($row->num_view>0)?$row->num_view:0;
				$data[$key]["rating"] = ($row->num_voter==0)?0:($row->rating/$row->num_voter);
				$data[$key]["tipe_layer"] = $row->tipe_layer;
				$data[$key]["id_csw_layer"] = $row->id;
			}
		}
		return array("total"=>$total,"rows"=>$data);
	}
	private function getRecordsResponseToArray($response){
		$result = $this->sipitung->xml2array($response);
		return $result["csw:GetRecordsResponse"];
	} 
	private function getRecordByIdResponseToArray($response){
		$result = $this->sipitung->xml2array($response);
		return $result["csw:GetRecordByIdResponse"];
	} 

	public function getRecordsById($id){
		$cswlayer = new Cswlayer();
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<csw:GetRecordById xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" service="CSW" version="2.0.2">
				  <csw:Id>'.$id.'</csw:Id>
				  <csw:ElementSetName>full</csw:ElementSetName>
				</csw:GetRecordById>';
		$client = new Zend_Http_Client($this->url);
		$client->setConfig(array('timeout'=>30));
		$response = $client->setRawData($xml, 'application/xml')->request('POST');
		$result = $this->getRecordByIdResponseToArray($response->getBody());
		if($result["csw:Record"]){
			$searchResult = $result["csw:Record"];
			$data=array(
				"identifier" =>$searchResult["dc:identifier"],
				"title"      =>$searchResult["dc:title"],
				"modified"   =>$searchResult["dct:modified"],
				"abstract"   =>$searchResult["dct:abstract"],
				"type"       =>(is_array($searchResult["dc:type"])?implode(",",$searchResult["dc:type"]):$searchResult["dc:type"]),
				"subject"    =>(is_array($searchResult["dc:subject"])?implode(",",$searchResult["dc:subject"]):$searchResult["dc:subject"]),
				"format"     =>$searchResult["dc:format"],
				"creator"    =>$searchResult["dc:creator"],
				"contributor"=>$searchResult["dc:contributor"],
				"language"   =>$searchResult["dc:language"]
			);
			$BoundingBox = $searchResult["ows:BoundingBox"];
			if($BoundingBox){
				$lowercorner = explode(" ",$BoundingBox["ows:LowerCorner"]);
				$uppercorner = explode(" ",$BoundingBox["ows:UpperCorner"]);
				$srs = $searchResult["ows:BoundingBox_attr"]["crs"];
			}
			$data["srs"] = $srs;
			$data["xmin"] = $lowercorner[0];
			$data["ymin"] = $lowercorner[1];
			$data["xmax"] = $uppercorner[0];
			$data["ymax"] = $uppercorner[1];
			$data["boundingbox"] = $lowercorner[0]." ".$lowercorner[1].", ".$uppercorner[0]." ".$uppercorner[1];

			$conf = Zend_Registry::get('config');
			$row = $cswlayer->fetchRow("identifier='$id'");
			$data["url"] = $conf["geoserver.url"]."/wms";
			$data["layer"] = $conf["geoserver.workspace"].":".$row->layer;
			$data["thumbnail"] = $conf["geoserver.workspace"].":".$row->layer.".png";
			$data["num_view"] = ($row->num_view>0)?$row->num_view:0;
			$data["rating"] = ($row->num_voter==0)?0:($row->rating/$row->num_voter);
			$data["tipe_layer"] = $row->tipe_layer;
			$data["id_csw_layer"] = $row->id;
		}else{
			$data = null;
		}
		return $data;
	}
	public function insert($xml){
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<csw:Transaction xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ows="http://www.opengis.net/ows" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/CSW-publication.xsd" service="CSW" version="2.0.2" outputFormat="json">
				<csw:Insert>
				  	'.$xml.'
				</csw:Insert>
				</csw:Transaction>';
		$client = new Zend_Http_Client($this->url);
		$client->setConfig(array('timeout'=>30));
		$response = $client->setRawData($xml, 'application/xml')->request('POST');	
		// print_r($response->getBody());
		return $response->getBody();	
	}
	public function update($xml){
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<csw:Transaction xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/CSW-publication.xsd" service="CSW" version="2.0.2" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ows="http://www.opengis.net/ows" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				<csw:Update>
					'.$xml.'
				</csw:Update>
				</csw:Transaction>';
		$client = new Zend_Http_Client($this->url);
		$client->setConfig(array('timeout'=>30));
		$response = $client->setRawData($xml, 'application/xml')->request('POST');
		return $response->getBody();	
	}
	public function delete($xml){
		$xml = '<csw:Transaction xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/CSW-publication.xsd" service="CSW" version="2.0.2" xmlns:ogc="http://www.opengis.net/ogc" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ows="http://www.opengis.net/ows" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				<csw:Delete>
				<csw:Constraint version="1.1.0">'.$xml.'</csw:Constraint>
				</csw:Delete>
				</csw:Transaction>';
		$client = new Zend_Http_Client($this->url);
		$response = $client->setRawData($xml, 'application/xml')->request('POST');	
		return $response->getBody();		
	}
	public function parseRecordsFromTable($data){
			$conf = Zend_Registry::get('config');
			$Sipitung = Zend_Controller_Action_HelperBroker::getStaticHelper('Sipitung');
			$xml = $Sipitung->xml2array($data["xml"]);
			$lowerCorner = $xml["csw:Record"]["ows:BoundingBox"]["ows:LowerCorner"];
			$upperCorner = $xml["csw:Record"]["ows:BoundingBox"]["ows:UpperCorner"];
			$boundingbox = $lowerCorner.", ".$upperCorner;
			$lowerCorner = explode(" ",$lowerCorner);
			$upperCorner = explode(" ",$upperCorner);
			$srs = $xml["csw:Record"]["ows:BoundingBox_attr"]["crs"];
			return array(
					"identifier" =>$data["identifier"],
					"id_csw_layer" =>$data["id"],
					"title"      =>$data["title"],
					"modified"   =>$data["modified"],
					"abstract"   =>$data["abstract"],
					"type"       =>$data["type"],
					"format"     =>$data["format"],
					"creator"    =>$data["creator"],
					"contributor"=>$data["contributor"],
					"language"   =>$data["language"],
					"srs"   	 =>$srs,
					"xmin"		 =>$lowerCorner[0],
					"ymin"		 =>$lowerCorner[1],
					"xmax"		 =>$upperCorner[0],
					"ymax"		 =>$upperCorner[1],
					"boundingbox"=>$boundingbox,
					"url"        =>$conf["geoserver.url"]."/wms",
					"layer"      =>$conf["geoserver.workspace"].":".$data["layer"],
					"thumbnail"  =>$conf["geoserver.workspace"].":".$data["layer"].".png",
					"num_view"   =>$data["num_view"],
					"rating"     =>$data["bintang"],
					"tipe_layer" =>$data["tipe_layer"]
				);		
	}
}
?>