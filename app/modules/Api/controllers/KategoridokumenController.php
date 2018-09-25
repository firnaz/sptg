<?php
class Api_KategoridokumenController extends Api_Controller_Action{
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
		
		$kategoridokumen = new Kategoridokumen();
		$data = $kategoridokumen->fetchAll($kategoridokumen->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_dokumen_kategori'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		echo json_encode(array("row"=>$data, "total"=>$total["total"]));
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$kategoridokumen = new Kategoridokumen();
		$row  = $kategoridokumen->createRow();
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

		$kategoridokumen = new Kategoridokumen();

		$data['kategori'] =$kategori;
		
		$where = $kategoridokumen->getAdapter()->quoteInto('id= ?', $id);
		$kategoridokumen->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$kategoridokumen = new Kategoridokumen();

		$where = $kategoridokumen->getAdapter()->quoteInto('id= ?', $id);
		$kategoridokumen->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>