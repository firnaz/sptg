<?php
class Api_PagesController extends Api_Controller_Action{
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
		
		$pages = new Pages();
		$data = $pages->fetchAll($pages->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_pages'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		echo json_encode(array("row"=>$data, "total"=>$total["total"]));
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$pages = new Pages();

		$row  = $pages->createRow();
		$row->nama_page 		= $nama_page;
		$row->konten 	= $konten;
		$uploaded = $this->uploadGambar();
		if(count($uploaded)){
			$row->nama_file_gambar_renamed = $uploaded["file_renamed"];
			$row->nama_file_gambar = $uploaded["file"];
			$row->content_type = $uploaded["content_type"];
			$row->waktu_upload 	= date("Y-m-d H:i:s");
		}
		$row->user_creator 	= "admin";
		$id  = $row->save();
		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$pages = new Pages();
		$current = $pages->fetchRow("id='$id'");

		$data['nama_page'] =$nama_page;
		$data['konten'] =$konten;
		if($noimages){
			$data["nama_file_gambar_renamed"] = NULL;
			$data["nama_file_gambar"]         = NULL;
			$data["content_type"]             = NULL;
			$data["waktu_upload"]             = NULL;
		}else{
			$uploaded = $this->uploadGambar();
			if(count($uploaded)){
				@unlink(GAMBAR_PAGES."/".$current->nama_file_gambar_renamed);
				$data["nama_file_gambar_renamed"] = $uploaded["file_renamed"];
				$data["nama_file_gambar"]         = $uploaded["file"];
				$data["content_type"]             = $uploaded["content_type"];
				$data["waktu_upload"]             = date("Y-m-d G:i:s");
			}
		}
		$where = $pages->getAdapter()->quoteInto('id= ?', $id);
		$pages->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$pages = new Pages();
		$current = $pages->fetchRow("id='$id'");
		@unlink(GAMBAR_PAGES."/".$current->nama_file_gambar_renamed);

		$where = $pages->getAdapter()->quoteInto('id= ?', $id);
		$pages->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}

	public function uploadGambar(){
		$upload = new Zend_File_Transfer_Adapter_Http();

		$upload->setDestination(GAMBAR_PAGES)->addValidator('Extension', false, array('jpg', 'png', 'gif'));
		
		$files  = $upload->getFileInfo();

		foreach($files as $file=>$info){
			if ($upload->isUploaded($file)) {
				if ($upload->isValid($file)) {
					$nama_file = basename($upload->getFileName($file));
					$file_ext = pathinfo($info['name'], PATHINFO_EXTENSION);
					$nama_file_renamed = md5(time()).".".$file_ext;
					$upload->addFilter(new Zend_Filter_File_Rename(array('target' => GAMBAR_PAGES."/".$nama_file_renamed, 'overwrite' => true)), null, $file);
					if($upload->receive($file)){
						return array("file_renamed"=>$nama_file_renamed, "file"=>$nama_file, "content_type"=>$upload->getMimeType($file));
					}
				}
			}
		}
		return false;
	}
}
?>