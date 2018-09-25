<?php
class Api_UseradminController extends Api_Controller_Action{
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
		
		$useradmin = new Useradmin();
		$data = $useradmin->fetchAll($useradmin->select()->from($useradmin, array('id'=>'username', 'username','nama_lengkap','email','last_login','is_active'))->order("$sort $dir")->where("username!='admin'")->limit($limit,$start));

		$total = $db->fetchRow($db->select()->from(array('t_user_admin'),array('count(*) as total'))->where("username!='admin'"));
		$data = count($data)?$data->toArray():array();
	
		
		echo json_encode(array("rows"=>$data, "total"=>$total["total"]));	
	}
	public function addAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$useradmin = new Useradmin();
		$checkData = $useradmin->fetchAll($useradmin->select()->where("username='$username'"));

		if (count($checkData)==0){
			$row  = $useradmin->createRow();
			$row->username 	= $username;
			$row->nama_lengkap 	= $nama_lengkap;
			$row->email 	= $email;
			$row->password 	= md5($password);
			$row->time_created 	= date('Y-m-d G:i:s');
			$row->last_login 	= null;
			$row->is_active 	= $is_active;
			$useradminid = $row->save();
			$result["success"] = "true";
		}else{
			$result["success"] = "false";
			$result["reason"] = "User already exists.";
		}
		echo Zend_Json::encode($result);
	}
	function editAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		extract($this->getRequest()->getParams());

		$useradmin = new Useradmin();
		$checkData = $useradmin->fetchAll($useradmin->select()->where("username='$id'"));

		if (count($checkData)==0 || $username==$id){
			$data['nama_lengkap'] =$nama_lengkap;
			$data['email'] =$email;
			$data['password'] =md5($password);
			$data['is_active'] =$is_active;
			
			$where = $useradmin->getAdapter()->quoteInto('username= ?', $id);
			$useradmin->update($data, $where);

			$result["success"] = "true";
		}else{
			$result["success"] = "false";
			$result["reason"] = "User already exists.";			
		}
		echo json_encode($result);
	}
	public function deleteAction()
	{
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		extract($this->getRequest()->getParams());
		
		$useradmin = new Useradmin();

		$where = $useradmin->getAdapter()->quoteInto('username= ?', $id);
		$useradmin->delete($where);
		$result["success"] = "true";
		echo json_encode($result);
	}}
?>