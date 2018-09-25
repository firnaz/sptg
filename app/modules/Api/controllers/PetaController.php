<?php
class Api_PetaController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'DESC'; 
			$sort='time_created';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}	
		
		$petagallery = new Petagallery();
		$data = $petagallery->fetchAll($petagallery->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_peta_gallery'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
		// foreach($data as $key=>$val){
		// 	if(!file_exists(PETA_THUMBNAIL."/peta-".$val["id"].".png")){
		// 		error_log(PETA_THUMBNAIL."/peta-".$val["id"].".png");
		// 		error_log($val["id"]);
		// 		$this->_helper->Sipitung->createPetaThumbnail($val["id"],$val["x_min"],$val["y_min"],$val["x_max"],$val["y_max"]);
		// 	}
		// 	$data[$key]["date"] = date('d F Y',strtotime($val['time_created']));
		// 	sleep(1);
		// }
		// print_r($this->config);exit;
	
		echo json_encode(array("rows"=>$data, "total"=>$total["total"]));
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$petagallery = new Petagallery();

		$url_segment = $this->_helper->Sipitung->generateURLSegment($judul);
		$checkdoc = $petagallery->fetchRow("url_segment='$url_segment'");
		$i=2;
		while(count($checkdoc)>0){
			$url_segment = $url_segment.$i;
			$checkdoc = $petagallery->fetchRow("url_segment='$url_segment'");
			$i++;
		}
		// print_r(json_decode("[".$layer."]"));exit;
		
		$row  = $petagallery->createRow();
		$row->judul        = $judul;
		$row->deskripsi    = $deskripsi;
		$row->time_created = date("Y-m-d G:i:s");
		$row->user_creator = $this->user_admin->username;
		$row->is_approved  = 1;
		$row->x_min        = $x_min;
		$row->y_min        = $y_min;
		$row->x_max        = $x_max;
		$row->y_max        = $y_max;
		$row->url_segment  = $url_segment;
		$id  = $row->save();

		$this->addLayer($id,$layer);
		$this->saveThumbnail($id, $ollayers, $x_min, $y_min, $x_max, $y_max);

		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$petagallery = new Petagallery();

		$url_segment = $this->_helper->Sipitung->generateURLSegment($judul);
		$checkdoc = $petagallery->fetchRow("url_segment='$url_segment' and id!='$id'");
		$i=2;
		while(count($checkdoc)>0){
			$url_segment = $url_segment.$i;
			$checkdoc = $petagallery->fetchRow("url_segment='$url_segment' and id!='$id'");
			$i++;
		}

		$data['judul']        = $judul;
		$data['deskripsi']    = $deskripsi;
		$data['is_approved']  = 1;
		$data['url_segment']  = $url_segment;
		$data['x_min']        = $x_min;
		$data['y_min']        = $y_min;
		$data['x_max']        = $x_max;
		$data['y_max']        = $y_max;
		$data['user_creator'] = $this->user_admin->username;
		
		$where = $petagallery->getAdapter()->quoteInto('id= ?', $id);
		$petagallery->update($data, $where);

		$this->addLayer($id,$layer);
		$this->saveThumbnail($id, $ollayers,$x_min, $y_min, $x_max, $y_max);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$petagallery = new Petagallery();

		$where = $petagallery->getAdapter()->quoteInto('id= ?', $id);
		$petagallery->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}
	public function availablelayerAction(){
		extract($this->getRequest()->getParams());
		$petalayer= new Petalayer();
		$cswlayer= new Cswlayer();
		$used = array();

		$sortXml = '<ogc:SortBy> 
					<ogc:SortProperty> 
					<ogc:PropertyName>dc:title</ogc:PropertyName> 
					<ogc:SortOrder>ASC</ogc:SortOrder> 
					</ogc:SortProperty> 
					</ogc:SortBy>'; 

		// $data = $this->_helper->PyCswClient->getRecords("", "json", $limit, $start, $sortXml);


		if ($id){
			$rows = $petalayer->fetchAll($petalayer->select()->order("urutan ASC")->where("id_peta='$id'"));
			foreach($rows as $row){
				if ($row->id_csw_layer){
					$layer = $cswlayer->fetchRow("id='".$row->id_csw_layer."'");
					$used[] = $layer->identifier;
				}
			}
			$filter="";
			if(count($used)){
				if(count($used)==1){
					$filter ="<ogc:Filter>
								<ogc:PropertyIsNotEqualTo>
								<ogc:PropertyName>dc:identifier</ogc:PropertyName>
								<ogc:Literal>".$used[0]."</ogc:Literal>
								</ogc:PropertyIsNotEqualTo>
							</ogc:Filter>";				
				}else{
					$filter ="<ogc:Filter><ogc:And>";
					foreach($used as $key=>$val){
						$filter.= "<ogc:PropertyIsNotEqualTo>
									<ogc:PropertyName>dc:identifier</ogc:PropertyName>
									<ogc:Literal>".$val."</ogc:Literal>
								</ogc:PropertyIsNotEqualTo>";
					}
					$filter .="</ogc:And></ogc:Filter>";				
				}
			}
			// echo $filter;exit;
			$data = $this->_helper->PyCswClient->getRecords($filter, 9999, 0, $sortXml);			
		}else{
			$data = $this->_helper->PyCswClient->getRecords("", 9999, 0, $sortXml);
		}
		echo json_encode($data);
	}
	public function selectedlayerAction(){
		extract($this->getRequest()->getParams());

		if ($id){
			$rows = $this->_helper->Sipitung->getLayersByPeta($id);
			$data = array("total"=>count($rows), "rows"=>$rows);
		}else{
			$data = array("total"=>0,"rows"=>array());
		}
		echo json_encode($data);
	}

	public function wmslayerAction(){
		extract($this->getRequest()->getParams());
		$data = $this->_helper->Sipitung->getWmsLayer();
		echo  Zend_Json::encode(array("rows"=>$data));
	} 

	public function addLayer($id, $layer){
		$layers = json_decode("[".$layer."]");
		$urutan = 1;
		$petalayer= new Petalayer();
		$cswlayer= new Cswlayer();
		$wmslayer= new Wmslayer();

		// delete current list
		$where = $petalayer->getAdapter()->quoteInto('id_peta= ?', $id);
		$petalayer->delete($where);

		//add layer list
		foreach($layers as $l){
			if($l->type=="external_wms"){
				$row = $wmslayer->createRow();
				$row->title = $l->title;
				$row->nama_layer = $l->layer;
				$row->srs = $l->srs;
				$row->x_min = $l->xmin;
				$row->y_min = $l->ymin;
				$row->x_max = $l->xmax;
				$row->y_max = $l->ymax;
				$row->url = $l->url;
				$idwmslayer = $row->save();
				$idcswlayer = 0;
			}else{
				$datacswlayer = $cswlayer->fetchRow("identifier='".$l->identifier."'");
				$idcswlayer = $datacswlayer->id;
				$idwmslayer = 0;
			}
			$row = $petalayer->createRow();
			$row->id_peta = $id;
			$row->id_csw_layer = $idcswlayer;
			$row->id_wms_layer = $idwmslayer;
			$row->urutan = $urutan;
			$row->on_off = 1;
			$row->save();
			$urutan++;
		}
	}
	public function saveThumbnail($id, $ollayers, $xmin, $ymin, $xmax, $ymax){
		$layers = json_decode($ollayers);
		// print_r($layers);exit;
		$i=0;
		$width = 1024;
		$xRatio = abs($xmax-$xmin);
		$yRatio = abs($ymax-$ymin);
		$height = floor($width*($yRatio/$xRatio));
		foreach($layers as $l){
			$wmsURL = substr($l->url, -1)=="?"?substr($l->url,0,-1):$l->url;
			$url = $wmsURL."?service=".$l->params->SERVICE."&version=".$l->params->VERSION."&request=GetMap&layers=".$l->params->LAYERS."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&srs=".$l->params->SRS."&format=image/png&styles=".$l->params->STYLES."&transparent=true";
			// echo $url;exit;
			@$handle = fopen($url, 'rb');
			if(is_resource($handle)){
				if (!$i){
					try{
						$thumbnail = new Imagick();
						$thumbnail ->readImageFile($handle);
					}catch(Exception $e){
						$thumbnail = null;
						continue;
					}
				}else{
					try{
						$img = new Imagick();
						$img->readImageFile($handle);
						$thumbnail->compositeImage( $img, imagick::COMPOSITE_DEFAULT, 0, 0 );
					}catch(Exception $e){
						continue;
					}
				}
				fclose($handle);
				$i++;
			}
		}
		if($thumbnail){
			$thumbnail->writeImage(PETA_THUMBNAIL."/peta-".$id.".png");
		}
	}
}
?>