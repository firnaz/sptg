<?php
class Api_WmsController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'ASC'; 
			$sort='nama';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}	
		
		$wms = new Wms();
		$data = $wms->fetchAll($wms->select()->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($wms->select()->from($wms,array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		echo json_encode(array("row"=>$data, "total"=>$total["total"]));
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$wms = new Wms();
		$row  = $wms->createRow();
		$row->nama 	= $nama;
		$row->url 	= substr($url,-1)=="?"?$url:$url."?";
		$row->save();
		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$wms = new Wms();

		$data['nama'] =$nama;
		$data['url'] =substr($url,-1)=="?"?$url:$url."?";
		
		$where = $wms->getAdapter()->quoteInto('id= ?', $id);
		$wms->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$wms = new Wms();

		$where = $wms->getAdapter()->quoteInto('id= ?', $id);
		$wms->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>