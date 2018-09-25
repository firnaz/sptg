<?php
class Api_LoginController extends Api_Controller_Action{
	public function loginAction(){
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();

		$uname = $this->getRequest()->getParam('username');
		$passwd = md5($this->getRequest()->getParam('password'));

		$authAdapter = new Zend_Auth_Adapter_DbTable($db,'t_user_admin','username','password', "? AND is_active='1'");
		// $selectAuth = $authAdapter->getDbSelect();
		// $selectAuth->where(" AND is_active='1'");
		// print_r($selectAuth);exit;
		$authAdapter->setIdentity($uname)->setCredential($passwd);

		$auth = Zend_Auth::getInstance();
		$authenticate = $auth->authenticate($authAdapter);
		$data = $authAdapter->getResultRowObject(null,'password');
		$auth->getStorage()->write($data);
		
		if(!$authenticate->isValid()){
			$auth  = Zend_Auth::getInstance();
		  	$auth->clearIdentity();
			Zend_Session::namespaceUnset("admin_auth");
			$result["success"] = "false";
			$result["reason"] = "Username dan Password Salah!!!";
		}else{
			$user_auth = new Zend_Session_Namespace('admin_auth');
			$user_auth->username=$auth->getIdentity()->username;
			$time = date("Y-m-d G:i:s");
			$user_auth->time= $time;
			$useradmin = new Useradmin();

			$d['last_login'] =$time;			
			$where = $useradmin->getAdapter()->quoteInto('username= ?', $uname);
			$useradmin->update($d, $where);

			$result["success"] = "true";
		}
		echo Zend_Json::encode($result);
	}

	public function logoutAction(){
		$auth  = Zend_Auth::getInstance();
	  	$auth->clearIdentity();
		Zend_Session::namespaceUnset("admin_auth");
		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}

	public function isloginAction(){
		$this->_helper->viewRenderer->setNoRender();
		$islogin = $this->_helper->Sipitung->checkAdminLogin();
		if ($islogin){
			$result = $islogin;
		}else{
			$result['success'] = "false"; 
		}
		echo Zend_Json::encode($result);
	}
	public function changepasswordAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		$useradmin = new Useradmin();
		extract($this->getRequest()->getParams());
		
		$username = $this->user_admin->username;
		$data['password'] = md5($password);
		$where = $useradmin->getAdapter()->quoteInto('username= ?', $username);
		$useradmin->update($data, $where);
		$result["success"] = "true";				
		echo json_encode($result);
	}
}
?>