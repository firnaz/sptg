<?php
class Web extends Zend_Controller_Action_Helper_Abstract {
	private $geoserver;
	private $pycsw;
	private $sipitung;
	private $db;

	public function __construct(){
		$this->geoserver = Zend_Controller_Action_HelperBroker::getStaticHelper('Geoserver');
		$this->sipitung = Zend_Controller_Action_HelperBroker::getStaticHelper('Sipitung');
		$this->pycsw = Zend_Controller_Action_HelperBroker::getStaticHelper('PyCswClient');
		$this->db = Zend_Db_Table_Abstract::getDefaultAdapter();

	}

	public function json_encode($array){
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setNoRender();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: x-requested-with");
        if($browser=="Internet Explorer"){
            header('Content-type: text/javascript');
        }else{
            header('Content-type: application/json');
        }
        echo json_encode($array);
	}
	public function getPeta($where,$limit=10,$start=0, $order="time_created DESC"){
		$petagallery = new Petagallery();
		$where = $where?$where:"1=1";
		// $where="1=1";
		$rows = $petagallery->fetchAll($petagallery->select()->from($petagallery,array('id','judul','deskripsi','time_created','user_creator','is_approved','url_segment', 'x_min', 'y_min', 'x_max', 'y_max', 'num_view','bintang'=>new Zend_Db_Expr("case when num_voter=0 then 0 else rating/num_voter end")))->order($order)->limit($limit,$start)->where($where));
		$rows = $rows->toArray();
		if(count($rows)){
			foreach($rows as $key=>$row){
				$rows[$key]["rating"]= $rows[$key]["bintang"];
				unset($rows[$key]["bintang"]);
				$rows[$key]["layers"]= array_reverse($this->sipitung->getLayersByPeta($row["id"]));
			}			
		}
		$pages = $this->createPages($petagallery, $limit, $start,$where);
		return array("rows"=>$rows, "pages"=>$pages);
	}

	public function getPetaByURLSegment($url_segment){
		$petagallery = new Petagallery();
		$row = $petagallery->fetchRow("url_segment='$url_segment'");
		$row = $row->toArray();
		$layers = array_reverse($this->sipitung->getLayersByPeta($row["id"]));
		$row["layers"] = $layers;
		return $row;
	}

	public function getDocs($where, $limit=5, $start=0, $order="time_created DESC"){
		$dokumen = new Dokumen();
		$where = $where?$where:"1=1";
		$rows = $dokumen->fetchAll($dokumen->select()->order($order)->limit($limit,$start)->where($where));
		$rows = $rows->toArray();
		$pages = $this->createPages($dokumen,$limit, $start,$where);
		return array("rows"=>$rows, "pages"=>$pages);
	}

	public function getDocsById($id){
		$dokumen = new Dokumen();
		$row = $dokumen->fetchRow("id='$id'");
		$row = $row->toArray();
		$row['files'] = $this->getDocFiles($id);
		return $row;
	}

	public function getDocFiles($id_dokumen){
		$dokumenfile = new Dokumenfile();
		$datadokumenfile = $dokumenfile->fetchAll("id_dokumen='$id_dokumen'");
		if($datadokumenfile){
			$data = $datadokumenfile->toArray();
			foreach($data as $key=>$val){
				$data[$key]= array(
						"id_file" => $val["id"],
						"filename" => $val["nama_file"],
						"filesize" => number_format(($val["file_size"]/1024),2)."KB",
						"filetype" => $val["content_type"],
						"url" => "dokumen/download/".$id_dokumen."/".$val["nama_file"]
					);
			}
			return $data;
		}else{
			return array();
		}
	}

	public function getArticles($where, $limit=2, $start=0){
		$artikel = new Artikel();
		$where = $where?$where:"1=1";
		$rows = $artikel->fetchAll($artikel->select()->order("time_created DESC")->limit($limit,$start)->where($where));
		$rows = $rows->toArray();
		foreach($rows as $key=>$val){
			$rows[$key]['konten'] = $this->character_limiter(strip_tags($val['konten']),500);
		}
		$pages = $this->createPages($artikel,$limit, $start,$where);
		return array("rows"=>$rows, "pages"=>$pages);
	}

	public function getArticleById($id){
		$artikel = new Artikel();
		$row = $artikel->fetchRow("id='$id'");
		$row = $row->toArray();
		$row['images'] = $this->getArticleImages($id);
		return $row;
	}

	public function getArticleImages($id_artikel){
		$artikelimages = new Artikelimages();
		$dataartikelimages = $artikelimages->fetchAll("id_artikel='$id_artikel'");
		if($dataartikelimages){
			$data = $dataartikelimages->toArray();
			foreach($data as $key=>$val){
				$data[$key]["url"] = "images/artikel/".$id_artikel."/600/400/".$val["nama_file"];
			}
			return $data;
		}else{
			return array();
		}
	}

	public function getArticleCategories(){
		$kategoriartikel = new Kategoriartikel();
		$datakategoriartikel = $kategoriartikel->fetchAll($kategoriartikel->select()->order("kategori ASC"));
		return $datakategoriartikel->toArray();
	}

	public function getArticleCategory($id_kategori){
		$kategoriartikel = new Kategoriartikel();
		$datakategoriartikel = $kategoriartikel->fetchRow("id='$id_kategori'");
		return $datakategoriartikel->toArray();
	}

	function character_limiter($str, $n = 200, $end_char = '&#8230;')
	{
		$str = strip_tags($str);
	    if (strlen($str) < $n)
	    {
	        return $str;
	    }

	    $str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));

	    if (strlen($str) <= $n)
	    {
	        return $str;
	    }

	    $out = "";
	    foreach (explode(' ', trim($str)) as $val)
	    {
	        $out .= $val.' ';

	        if (strlen($out) >= $n)
	        {
	            $out = trim($out);
	            return (strlen($out) == strlen($str)) ? $out : $out.$end_char;
	        }
	    }
	 }
	 public function createPages($model, $limit, $start, $where){
		$total = $this->db->fetchRow($model->select()->from($model,array('count(*) as total'))->where($where));
		$totalpages = ceil($total["total"]/$limit);
		if($start){
			$currentpage = ceil(($start+$limit)/$limit);
			$prev = $currentpage-1;
		}else{
			$currentpage = 1;
			$prev=false;
		}

		if($currentpage>=$totalpages){
			$next=false;
		}else{
			$next = $currentpage+1; 
		}

		return array(
				"max"=>$totalpages,
				"current"=>$currentpage, 
				"prev"=>$prev, 
				"next"=>$next
			);
	 }

	public function getLayerList($filter, $limit, $start, $sort=""){
		if(!$sort){
			$sort = '<ogc:SortBy> 
						<ogc:SortProperty> 
							<ogc:PropertyName>dc:title</ogc:PropertyName> 
							<ogc:SortOrder>ASC</ogc:SortOrder> 
						</ogc:SortProperty> 
					 </ogc:SortBy>'; 
		}
		$result = $this->pycsw->getRecords($filter, $limit, $start, $sort);
		$totalpages = ceil($result["total"]/$limit);
		if($start){
			$currentpage = ceil(($start+$limit)/$limit);
			$prev = $currentpage-1;
		}else{
			$currentpage = 1;
			$prev=false;
		}

		if($currentpage>=$totalpages){
			$next=false;
		}else{
			$next = $currentpage+1; 
		}

		$pages = array(
				"max"=>$totalpages,
				"current"=>$currentpage, 
				"prev"=>$prev, 
				"next"=>$next
			);
		return array("rows"=>$result["rows"],"pages"=>$pages);
	}
	public function getLayersByExtent($andfilter, $xmin, $ymin, $xmax, $ymax, $limit, $start){
		$bboxfilter = "<ogc:BBOX>
							<ogc:PropertyName>ows:BoundingBox</ogc:PropertyName>
							<gml:Envelope>
								<gml:lowerCorner>$xmin $ymin</gml:lowerCorner>
								<gml:upperCorner>$xmax $ymax</gml:upperCorner>
							</gml:Envelope>
					    </ogc:BBOX>";
		if($andfilter){
			$filter = "<ogc:Filter><ogc:And>".$andfilter.$bboxfilter."</ogc:And></ogc:Filter>";
		}else{
			$filter = "<ogc:Filter>".$bboxfilter."</ogc:Filter>";			
		}
		return $this->getLayerList($filter, $limit, $start);
	}

	public function getLayerCategories(){
		$kategorilayer = new Kategorilayer();
		$datakategorilayer = $kategorilayer->fetchAll($kategorilayer->select()->order("nama_kategori ASC"));
		return $datakategorilayer->toArray();
	}

	public function getLayerCategory($id_kategori){
		$kategorilayer = new Kategorilayer();
		$datakategorilayer = $kategorilayer->fetchRow("id='$id_kategori'");
		return $datakategorilayer->toArray();
	}
	public function getLayers($where, $limit, $start, $order='date_modified DESC'){
		$where = $where?$where:"1=1";
		$datacsw = $this->db->fetchAll($this->db->select()->from(array("a"=>"t_csw_layer"), array("a.*","bintang"=>new Zend_Db_Expr("case when num_voter=0 then 0 else rating/num_voter end")))->join(array("b"=>"records"),'b.identifier=a.identifier',array('b.*'))->order($order)->where($where)->limit($limit,$start));

		$total = $this->db->fetchRow($this->db->select()->from(array("a"=>"t_csw_layer"), array("total"=>"count(*)"))->join(array("b"=>"records"),'b.identifier=a.identifier',array())->where($where));
		$result = array();
		foreach($datacsw as $data){
			$result[]= $this->pycsw->parseRecordsFromTable($data);
		}
		$totalpages = ceil($total["total"]/$limit);
		if($start){
			$currentpage = ceil(($start+$limit)/$limit);
			$prev = $currentpage-1;
		}else{
			$currentpage = 1;
			$prev=false;
		}

		if($currentpage>=$totalpages){
			$next=false;
		}else{
			$next = $currentpage+1; 
		}

		$pages = array(
				"max"=>$totalpages,
				"current"=>$currentpage, 
				"prev"=>$prev, 
				"next"=>$next
			);
		return array("rows"=>$result,"pages"=>$pages);
	}
	public function getLayerByIdentifier($identifier){
		$where = "a.identifier='$identifier'";

		$data = $this->db->fetchRow($this->db->select()->from(array("a"=>"t_csw_layer"), array("a.*","bintang"=>new Zend_Db_Expr("case when num_voter=0 then 0 else rating/num_voter end")))->join(array("b"=>"records"),'b.identifier=a.identifier',array('b.*'))->order($order)->where($where)->limit($limit,$start));
		$result= $this->pycsw->parseRecordsFromTable($data);
		return $result;
	}
	public function getLayerCount($tipe){
		$cswlayer = new Cswlayer();
		$countcswlayer=$cswlayer->fetchRow($cswlayer->select()->from($cswlayer,array("count(*) as total"))->where("tipe_layer='".$tipe."'"));

		return $countcswlayer->total;
	}
	public function getDocumentCategories(){
		$kategoridokumen = new Kategoridokumen();
		$data = $kategoridokumen->fetchAll($kategoridokumen->select()->order("kategori ASC"));
		return $data->toArray();
	}
	public function getDocumentCategoryById($id_kategori){
		$kategoridokumen = new Kategoridokumen();
		$data = $kategoridokumen->fetchRow("id_kategori='".$id_kategori."'");
		return $data->toArray();
	}
	public function getWmsLayer(){
		$dataWms = $this->sipitung->getWmsLayer();
		$tmp = array();
		$data = array();
		foreach($dataWms as $key=>$val){
			$tmp[$val['kategori']][] = array("name"=>$val["nama"],"url"=>$val["url"]);
		}
		foreach($tmp as $key=>$val){
			$data[] = array(
					"name"=> $key,
					"list"=>$val
				);
		}
		return $data;
	}
	public function updateLayerViews($identifier){
		$cswlayer = new Cswlayer();
		$datalayer = $cswlayer->fetchRow("identifier='$identifier'");

		$datalayer->num_view=$datalayer->num_view+1;
		$datalayer->save();
	}
	public function updatePetaViews($url_segment){
		error_log("updatePetaViews");
		$petagallery = new Petagallery();
		$datapetagallery = $petagallery->fetchRow("url_segment='$url_segment'");

		$datapetagallery->num_view++;
		$datapetagallery->save();
	}
	public function getChildren(){
		$children = new Children();
		$data= $children->fetchAll($children->select()->order("nama_sistem ASC"));
		return count($data)?$data->toArray():array();
	}
	public function petaThumbnail($layers, $xmin, $ymin, $xmax, $ymax){
		// print_r($layers);exit;
		$layers = array_reverse($layers);
		$i=0;
		$width = 1024;
		$xRatio = abs($xmax-$xmin);
		$yRatio = abs($ymax-$ymin);
		$height = floor($width*($yRatio/$xRatio));

		$baselayer = array(
						"url"=> "http://ows.terrestris.de/osm/service?",
						"layers"=> "OSM-WMS",
						"versions"=> "1.1.1",
						"styles" => "",
						"srs"=> "EPSG:4326"
					); 
		
		$wmsURL = substr($baselayer["url"], -1)=="?"?substr($baselayer["url"],0,-1):$baselayer["url"];
		$url = $wmsURL."?service=WMS&version=1.1.1&request=GetMap&layers=".$baselayer["layers"]."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&srs=".$baselayer["srs"]."&format=image/png&styles=".$baselayer["styles"]."&transparent=true";
		@$handle = fopen($url, 'rb');
		if(is_resource($handle)){
			try{
				$thumbnail = new Imagick();
				$thumbnail->readImageFile($handle);
			}catch(Exception $e){
				$thumbnail = null;
			}
			fclose($handle);
		}
		$context = stream_context_create(array('http'=>array('timeout'=>30000000)));
		foreach($layers as $l){
			$wmsURL = substr($l->url, -1)=="?"?substr($l->url,0,-1):$l->url;
			$url = $wmsURL."?SERVICE=WMS&VERSION=".(isset($l->params->VERSION)?$l->params->VERSION:"1.3.0")."&REQUEST=GetMap&LAYERS=".$l->params->LAYERS."&BBOX=".(isset($l->params->VERSION)&&$l->params->VERSION!="1.3.0"?"$xmin,$ymin,$xmax,$ymax":"$ymin,$xmin,$ymax,$xmax")."&WIDTH=".$width."&HEIGHT=".$height."&".(isset($l->params->VERSION)&&$l->params->VERSION!="1.3.0"?"SRS":"CRS")."=".(isset($l->params->SRS)?$l->params->SRS:"EPSG:4326")."&FORMAT=image/png&STYLES=&TRANSPARENT=true";
			@$handle = fopen($url, 'rb');
			if (is_resource($handle)){
				$img = new Imagick();
				try{
					$img->readImageFile($handle);
					if($thumbnail){
						$thumbnail->compositeImage( $img, imagick::COMPOSITE_DEFAULT, 0, 0 );
					}else{
						$thumbnail=$img;
					}
				}catch(Exception $e){
					continue;
				}
				fclose($handle);
			}
			$i++;
		}
		return $thumbnail;
	}

}
?>