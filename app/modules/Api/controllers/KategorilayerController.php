<?php
class Api_KategorilayerController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'ASC'; 
			$sort='nama_kategori';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}	
		
		$kategorilayer = new Kategorilayer();
		$data = $kategorilayer->fetchAll($kategorilayer->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_kategori_layer'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		echo json_encode(array("row"=>$data, "total"=>$total["total"]));
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$kategorilayer = new Kategorilayer();
		$row  = $kategorilayer->createRow();
		$row->nama_kategori 	= $nama_kategori;
		$row->save();
		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$kategorilayer = new Kategorilayer();

		$data['nama_kategori'] =$nama_kategori;
		
		$where = $kategorilayer->getAdapter()->quoteInto('id= ?', $id);
		$kategorilayer->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$kategorilayer = new Kategorilayer();

		$where = $kategorilayer->getAdapter()->quoteInto('id= ?', $id);
		$kategorilayer->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>