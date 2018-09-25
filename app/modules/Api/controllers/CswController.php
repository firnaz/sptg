<?php
class Api_CswController extends Api_Controller_Action{
	private $fields=array(
				"title"       => "dc:title",
				"modified"    => "dct:modified",
				"abstract"    => "dct:abstract",
				"type"        => "dc:type",
				"subject"     => "dc:subject",
				"format"      => "dc:format",
				"publisher"   => "dc:publisher",
				"creator"     => "dc:creator",
				"language"    => "dc:language",
				"contributor" => "dc:contributor"
			);
	public function viewAction(){
		extract($this->getRequest()->getParams());
		if(!$sort) { 
			$dir = 'ASC'; 
			$sort='title';
		}else{
			$order = json_decode($sort);
			$dir   = $order[0]->direction;
			$sort  = $order[0]->property;
		}	

		$sortXml = '<ogc:SortBy> 
					<ogc:SortProperty> 
					<ogc:PropertyName>'.$this->fields[$sort].'</ogc:PropertyName> 
					<ogc:SortOrder>'.$dir.'</ogc:SortOrder> 
					</ogc:SortProperty> 
					</ogc:SortBy>'; 

		$limit = $limit?$limit:20;
		$data = $this->_helper->PyCswClient->getRecords("", $limit, $start, $sortXml);
		foreach($data["rows"] as $key=>$csw){
			if(!file_exists(LAYER_THUMBNAIL."/".$csw["thumbnail"])){
				$this->_helper->Geoserver->createThumbnail($csw["layer"], 1024, $csw["srs"], $csw["xmin"], $csw["ymin"], $csw["xmax"], $csw["ymax"]);
			}
		}

		echo json_encode($data);
	}
	public function addAction(){
		extract($this->getRequest()->getParams());

		$dir= TMP_DIR."/shp";
		$upload = new Zend_File_Transfer_Adapter_Http();

		$upload->setDestination($dir)->addValidator('Extension', false, 'zip');
		$files  = $upload->getFileInfo();
		$uploaded = false;
		foreach($files as $file=>$info){
			if ($upload->isUploaded($file)) {
				if ($upload->isValid($file)) {
					if($upload->receive($file)){
						$extension = pathinfo($info['name'], PATHINFO_EXTENSION); 
						$layer_file = $upload->getFileName($file);
						$uploaded = true;
						//echo $nama_file;exit;
						//$zip_file = $dir."/".$nama_file;
					}else{
						$reason = "Upload Gagal.";						
					}
				}else{
					$reason = "Tipe file salah.";
				}
			}
		}
		if($uploaded){
			if($tipe_layer=="vektor"){
				$addLayer= $this->_helper->Geoserver->addFeature($title, $abstract, $layer_file);
			}else{
				$addLayer= $this->_helper->Geoserver->addCoverage($title, $abstract, $layer_file);
			}
			@unlink($layer_file);
			if($addLayer["success"]){
				// $subj = explode(",",$subject);
				$xmlSubject = "";
				foreach ($subject as $key=>$val){
					$xmlSubject .= '<dc:subject>'.$val.'</dc:subject>';
				}
				$identifier = uniqid();
				$xml = '<csw:Record xmlns="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/record.xsd">
					    <dc:identifier>'.$identifier.'</dc:identifier>
					    <dc:title>'.$title.'</dc:title>
					    <dct:modified>'.date('Y-m-d').'</dct:modified>
					    <dct:abstract>'.$abstract.'</dct:abstract>
					    <dc:type>dataset</dc:type><dc:type>service</dc:type>'.$xmlSubject.'
					    <dc:format>'.$this->config["default_layer_format"].'</dc:format>
					    <dc:creator>'.$this->config["organization"].'</dc:creator>
					    <dc:publisher>'.$this->config["organization"].'</dc:publisher>
					    <dc:contributor>'.$this->user_admin->username.'</dc:contributor>
					    <dc:language>id</dc:language>
					    <ows:BoundingBox crs="EPSG:'.$addLayer["epsg"].'">
					      <ows:LowerCorner>'.$addLayer['extent']['xmin'].' '.$addLayer['extent']['ymin'].'</ows:LowerCorner>
					      <ows:UpperCorner>'.$addLayer['extent']['xmax'].' '.$addLayer['extent']['ymax'].'</ows:UpperCorner>
					    </ows:BoundingBox>
					  </csw:Record>';
				$response= $this->_helper->Sipitung->xml2array($this->_helper->PyCswClient->insert($xml));
				if($response["csw:TransactionResponse"]["csw:TransactionSummary"]["csw:totalInserted"]){
					$cswlayer = new Cswlayer();
					$row = $cswlayer->createRow();
					$row->identifier = $identifier;
					$row->layer = $addLayer["name"];
					$row->tipe_layer = $tipe_layer;
					$row->save();

					$identifier = $response["csw:TransactionResponse"]["csw:InsertResult"]["csw:BriefRecord"]["dc:identifier"];
					$result["success"] = "true";
				}else{
					$result["success"] = "false";
					$result["reason"] = "Gagal menambah Layer!";
				}
			}else{
				$result["success"] = "false";
				$result["reason"] = $addLayer["reason"];
			}			
		}else{
			$result["success"] = "false";
			$result["reason"] = $reason;			
		}

		echo Zend_Json::encode($result);			
	}
	public function editAction(){
		extract($this->getRequest()->getParams());

		$data= $this->_helper->PyCswClient->getRecordsById($identifier);

		$cswlayer = new Cswlayer();
		$datacswlayer = $cswlayer->fetchRow("identifier='$identifier'");
		if($datacswlayer->tipe_layer=='vektor'){
			$this->_helper->Geoserver->updateFeature($title, $abstract, $datacswlayer->layer);
		}else{
			$this->_helper->Geoserver->updateCoverage($title, $abstract, $datacswlayer->layer);
		}

		$xmlSubject = "";
		foreach ($subject as $key=>$val){
			$xmlSubject .= '<dc:subject>'.$val.'</dc:subject>';
		}
		$xml = '<csw:Record xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/record.xsd" xmlns="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
			    <dc:identifier>'.$data['identifier'].'</dc:identifier>
			    <dc:title>'.$title.'</dc:title>
			    <dct:modified>'.date('Y-m-d').'</dct:modified>
			    <dct:abstract>'.$abstract.'</dct:abstract>
			    <dc:type>dataset</dc:type><dc:type>service</dc:type>'.$xmlSubject.'
			    <dc:format>'.$this->config["default_layer_format"].'</dc:format>
			    <dc:creator>'.$this->config["organization"].'</dc:creator>
			    <dc:publisher>'.$this->config["organization"].'</dc:publisher>
			    <dc:contributor>'.$this->user_admin->username.'</dc:contributor>
			    <dc:language>id</dc:language>
			    <ows:BoundingBox crs="'.$data["srs"].'">
			      <ows:LowerCorner>'.$data['xmin'].' '.$data['ymin'].'</ows:LowerCorner>
			      <ows:UpperCorner>'.$data['xmax'].' '.$data['ymax'].'</ows:UpperCorner>
			    </ows:BoundingBox>
			  </csw:Record>';
		$response= $this->_helper->Sipitung->xml2array($this->_helper->PyCswClient->update($xml));

		if($response["csw:TransactionResponse"]["csw:TransactionSummary"]["csw:totalUpdated"]){
			$identifier = $response["csw:TransactionResponse"]["csw:InsertResult"]["csw:BriefRecord"]["dc:identifier"];
			$result["success"] = "true";
		}else{
			$result["success"] = "false";
			$result["reason"] = "Gagal mengubah metadata!";
		}
		echo Zend_Json::encode($result);
	}
	public function deleteAction(){
		extract($this->getRequest()->getParams());

		$data= $this->_helper->PyCswClient->getRecordsById($identifier);
		$cswlayer = new Cswlayer();
		$datacswlayer = $cswlayer->fetchRow("identifier='$identifier'");

		//delete layer from geoserver
		if($datacswlayer->tipe_layer=='vektor'){
			$this->_helper->Geoserver->deleteFeature($datacswlayer->layer);
		}else{
			$this->_helper->Geoserver->deleteCoverage($datacswlayer->layer);
		}
		$this->_helper->Geoserver->deleteStyle($datacswlayer->layer."_style");
		$xml = '<ogc:Filter>
					<ogc:PropertyIsEqualTo>
						<ogc:PropertyName>apiso:Identifier</ogc:PropertyName>
						<ogc:Literal>'.$identifier.'</ogc:Literal>
					</ogc:PropertyIsEqualTo>
				</ogc:Filter>';
		$response= $this->_helper->Sipitung->xml2array($this->_helper->PyCswClient->delete($xml));
		if($response["csw:TransactionResponse"]["csw:TransactionSummary"]["csw:totalDeleted"]){
			$cswlayer = new Cswlayer();
			$where = $cswlayer->getAdapter()->quoteInto('identifier= ?', $identifier);
			$cswlayer->delete($where);
			$result["success"] = "true";
		}else{
			$result["success"] = "false";
			$result["reason"] = "Gagal menghapus Layer!";
		}

		echo Zend_Json::encode($result);
	}
	public function testAction(){
		$data= $this->_helper->PyCswClient->getRecordsById("5453c7930798d");
		print_r($data);
	}
}
?>