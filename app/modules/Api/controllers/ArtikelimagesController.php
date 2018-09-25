<?php
class Api_ArtikelimagesController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'DESC'; 
			$sort='waktu_upload';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}	

		$where = "1=1";

		if($id_artikel){
			$where .= " and id_artikel='$id_artikel'";
		}
		
		$artikelimages = new Artikelimages();
		$data = $artikelimages->fetchAll($artikelimages->select()->order("$sort $dir")->where($where));

		$total = $db->fetchRow($db->select()->from(array('t_artikel_images'),array('count(*) as total'))->where($where));
		$data = count($data)?$data->toArray():array();
	
		
		echo json_encode(array("rows"=>$data, "total"=>$total["total"]));	
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();		
		extract($this->getRequest()->getParams());

		$artikelimages = new Artikelimages();

		$upload = new Zend_File_Transfer_Adapter_Http();

		$upload->setDestination(ARTIKEL_IMAGES)->addValidator('Extension', false, array('jpg', 'png', 'tiff', 'tif', 'gif'));
		
		$files  = $upload->getFileInfo();

		foreach($files as $file=>$info){
			if ($upload->isUploaded($file)) {
				if ($upload->isValid($file)) {
					$nama_file = $upload->getFileName($file);
					$file_ext = pathinfo($nama_file, PATHINFO_EXTENSION);
					$nama_file_renamed = md5(time()).".".$file_ext;
					$upload->addFilter(new Zend_Filter_File_Rename(array('target' => ARTIKEL_IMAGES."/".$nama_file_renamed, 'overwrite' => true)), null, $file);
					if($upload->receive($file)){
						$artikelimagesrow                    = $artikelimages->createRow();
						$artikelimagesrow->nama_file         = basename($nama_file);
						$artikelimagesrow->nama_file_renamed = $nama_file_renamed;
						$artikelimagesrow->path_relatif      = ARTIKEL_IMAGES."/".$nama_file_renamed;
						$artikelimagesrow->content_type      = $upload->getMimeType($file);
						$artikelimagesrow->file_size         = filesize(ARTIKEL_IMAGES."/".$nama_file_renamed);
						$artikelimagesrow->waktu_upload      = date('Y-m-d G:i:s');
						$artikelimagesrow->id_artikel        = $id_artikel;
						$artikelimagesrow->user_creator      = $this->user_admin->username;
						$artikelimagesrow->save();
						$result["success"] = "true";
						echo Zend_Json::encode($result);
					}else{
						$result["success"] = "false";
						$result["reason"] = "Upload failed";
						echo Zend_Json::encode($result);						
					}
				}
			}
		}
	}

	public function deleteAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$artikelimages = new Artikelimages();

		$where = $artikelimages->getAdapter()->quoteInto('id= ?', $id);
		$data = $artikelimages->fetchRow($where);
		$file = ARTIKEL_IMAGES."/".$data->path_relatif;
		if(file_exists($file)){
			@unlink($file);
		}
		$artikelimages->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>