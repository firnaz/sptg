<?php
class Api_ChildrenController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'ASC'; 
			$sort='nama_sistem';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}	
		
		$children = new Children();
		$data = $children->fetchAll($children->select()->from($children, array('id'=>'id','username','password','nama_sistem','instansi','ip','url_publik','url_wms','url_api'))->order("$sort $dir")->limit($limit,$start));

		$total = $db->fetchRow($children->select()->from($children,array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
	
		echo json_encode(array("row"=>$data, "total"=>$total["total"]));
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$children = new Children();
		$row  = $children->createRow();
		$row->username    = trim($username);
		$row->password    = trim($password);
		$row->nama_sistem = $nama_sistem;
		$row->instansi    = $instansi;
		$row->ip          = $ip;
		$row->url_publik  = $url_publik;
		$row->url_wms     = substr($url_wms,-1)=="?"?$url_wms:$url_wms."?";
		$row->url_api     = $url_api;
		$row->save();
		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$children = new Children();

		$data['username'] =trim($username);
		$data['password'] = trim($password);
		$data['nama_sistem'] =$nama_sistem;
		$data['instansi'] =$instansi;
		$data['ip'] =$ip;
		$data['url_publik'] =$url_publik;
		$data['url_wms'] =substr($url_wms,-1)=="?"?$url_wms:$url_wms."?";
		$data['url_api'] =$url_api;
		
		$where = $children->getAdapter()->quoteInto('id= ?', $id);
		$children->update($data, $where);

		$result["success"] = "true";
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$children = new Children();

		$where = $children->getAdapter()->quoteInto('id= ?', $id);
		$children->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>