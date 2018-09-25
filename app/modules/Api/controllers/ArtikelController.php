<?php
class Api_ArtikelController extends Api_Controller_Action{
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
		
		$artikel = new Artikel();
		$data = $artikel->fetchAll($artikel->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_artikel'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		foreach($data as $key=>$val){
			$data[$key]["date"] = date('d F Y',strtotime($val['time_created']));
		}

		echo json_encode(array("rows"=>$data, "total"=>$total["total"]));	
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$artikel = new Artikel();
		$row  = $artikel->createRow();
		$row->judul 	= $judul;
		$row->author 	= $author;
		$row->konten 	= $konten;
		$row->time_created 	= date('Y-m-d G:i:s');
		$row->id_kategori 	= $id_kategori;
		$row->user_created 	= $this->user_admin->username;
		$artikelid = $row->save();

		// $upload = new Zend_File_Transfer_Adapter_Http();

		// $upload->setDestination(ARTIKEL_IMAGES)->addValidator('Extension', false, array('jpg', 'png', 'gif'));
		
		// $files  = $upload->getFileInfo();

		// foreach($files as $file=>$info){
		// 	if ($upload->isUploaded($file)) {
		// 		if ($upload->isValid($file)) {
		// 			$nama_file = $upload->getFileName($file);
		// 			$file_ext = $upload->getFileExtension($nama_file);
		// 			$nama_file_renamed = md5(mktime()).".".$file_ext;
		// 			$upload->addFilter(new Zend_Filter_File_Rename(array('target' => ARTIKEL_IMAGES.$nama_file_renamed, 'overwrite' => true)), null, $file);
		// 			if($upload->receive($file)){
		// 				$artikelimagesrow = $artikelimages->createRow();
		// 				$artikelimagesrow->nama_file  = $nama_file;
		// 				$artikelimagesrow->nama_file_renamed = $nama_file_renamed;
		// 				$artikelimagesrow->file_size = $upload->getFileSize($file);
		// 				$artikelimagesrow->content_type = $upload->getMimeType($file);
		// 				$artikelimagesrow->waktu_upload = date('Y-m-d H:i:s');
		// 				$artikelimagesrow->id_artikel = $artikelid;
		// 				$artikelimagesrow->save();
		// 			}
		// 		}
		// 	}
		// }

		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$artikel = new Artikel();

		$data['judul'] =$judul;
		$data['author'] =$author;
		$data['konten'] =$konten;
		$data['id_kategori'] =$id_kategori;
		$data['user_created'] =$this->user_admin->username;
		
		$where = $artikel->getAdapter()->quoteInto('id= ?', $id);
		$artikel->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$artikel = new Artikel();

		$where = $artikel->getAdapter()->quoteInto('id= ?', $id);
		$artikel->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>