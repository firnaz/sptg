<?php
class Api_DokumenController extends Api_Controller_Action{
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
		
		$dokumen = new Dokumen();
		$data = $dokumen->fetchAll($dokumen->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_dokumen'),array('count(*) as total')));
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

		$dokumen = new Dokumen();
		$row  = $dokumen->createRow();
		$row->judul 	= $judul;
		$row->author 	= $author;
		$row->konten 	= $konten;
		$row->time_created 	= date('Y-m-d G:i:s');
		$row->id_kategori 	= $id_kategori;
		$row->user_creator 	= $this->user_admin->username;
		$row->id_peta 	= $id_peta?$id_peta:0;
		$artikelid = $row->save();

		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$dokumen = new Dokumen();

		$data['judul'] =$judul;
		$data['author'] =$author;
		$data['konten'] =$konten;
		$data['id_kategori'] =$id_kategori;
		$data['id_peta'] =$id_peta?$id_peta:0;
		$data['user_creator'] =$this->user_admin->username;
		
		$where = $dokumen->getAdapter()->quoteInto('id= ?', $id);
		$dokumen->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$dokumen = new Dokumen();

		$where = $dokumen->getAdapter()->quoteInto('id= ?', $id);
		$dokumen->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>