<?php
class Api_DokumenfileController extends Api_Controller_Action{
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

		if($id_dokumen){
			$where .= " and id_dokumen='$id_dokumen'";
		}
		
		$dokumenfile = new Dokumenfile();
		$data = $dokumenfile->fetchAll($dokumenfile->select()->order("$sort $dir")->where($where));

		$total = $db->fetchRow($db->select()->from(array('t_dokumen_file'),array('count(*) as total'))->where($where));
		$data = count($data)?$data->toArray():array();
		foreach($data as $key=>$val){
			if(file_exists(DOKUMEN_FILE."/".$val["nama_file_renamed"])){
				$data[$key]["can_downloaded"]= true;
			}else{
				$data[$key]["can_downloaded"]= false;				
			}
		}	
		
		echo json_encode(array("rows"=>$data, "total"=>$total["total"]));	
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();		
		extract($this->getRequest()->getParams());

		$dokumenfile = new Dokumenfile();

		$upload = new Zend_File_Transfer_Adapter_Http();

		$upload->setDestination(DOKUMEN_FILE)->addValidator('Extension', false, array('doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'jpg', 'png', 'tiff', 'tif'));
		
		$files  = $upload->getFileInfo();

		foreach($files as $file=>$info){
			if ($upload->isUploaded($file)) {
				if ($upload->isValid($file)) {
					$nama_file = $upload->getFileName($file);
					$file_ext = pathinfo($nama_file, PATHINFO_EXTENSION);
					$nama_file_renamed = md5(time()).".".$file_ext;
					$upload->addFilter(new Zend_Filter_File_Rename(array('target' => DOKUMEN_FILE."/".$nama_file_renamed, 'overwrite' => true)), null, $file);
					if($upload->receive($file)){
						$dokumenfilerow                    = $dokumenfile->createRow();
						$dokumenfilerow->nama_file         = basename($nama_file);
						$dokumenfilerow->nama_file_renamed = $nama_file_renamed;
						$dokumenfilerow->path_relatif      = DOKUMEN_FILE."/".$nama_file_renamed;
						$dokumenfilerow->content_type      = $upload->getMimeType($file);
						$dokumenfilerow->file_size         = filesize(DOKUMEN_FILE."/".$nama_file_renamed);
						$dokumenfilerow->waktu_upload      = date('Y-m-d G:i:s');
						$dokumenfilerow->id_dokumen        = $id_dokumen;
						$dokumenfilerow->user_creator      = $this->user_admin->username;
						$dokumenfilerow->save();
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
		
		$dokumenfile = new Dokumenfile();

		$where = $dokumenfile->getAdapter()->quoteInto('id= ?', $id);
		$data = $dokumenfile->fetchRow($where);
		$file = DOKUMEN_FILE."/".$data->nama_file_renamed;
		if(file_exists($file)){
			@unlink($file);
		}
		$dokumenfile->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>