<?php
class Member_Controller_Action extends Zend_Controller_Action
{
	public $member_auth;
	public $db;
	public function init(){
		$this->db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$conf = Zend_Registry::get('config');

		$this->config = $conf;
		$controller = $this->getRequest()->getParam("controller");
		$action = $this->getRequest()->getParam("action");
		if($controller == "index" && $action=="login"){
		}else{
	    	$islogin = $this->_helper->Members->checkMemberLogin();
	    	if(!$islogin){
				$this->_redirect('/member/login');
	    	}else{
	    		$this->member_auth = new Zend_Session_Namespace('member_auth');
	    	}
	    }
	}
}
?>