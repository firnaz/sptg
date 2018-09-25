<?php
class Api_KategoriArtikelController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'ASC'; 
			$sort='kategori';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}	
		
		$kategoriartikel = new Kategoriartikel();
		$data = $kategoriartikel->fetchAll($kategoriartikel->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_artikel_kategori'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		echo json_encode(array("row"=>$data, "total"=>$total["total"]));
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$kategoriartikel = new Kategoriartikel();
		$row  = $kategoriartikel->createRow();
		$row->kategori 	= $kategori;
		$row->save();
		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$kategoriartikel = new Kategoriartikel();

		$data['kategori'] =$kategori;
		
		$where = $kategoriartikel->getAdapter()->quoteInto('id= ?', $id);
		$kategoriartikel->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$kategoriartikel = new Kategoriartikel();

		$where = $kategoriartikel->getAdapter()->quoteInto('id= ?', $id);
		$kategoriartikel->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>