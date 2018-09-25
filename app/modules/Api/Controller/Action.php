<?php
class Api_Controller_Action extends Zend_Controller_Action
{
	public function init(){
		$conf = Zend_Registry::get('config');
    	$controller = $this->getRequest()->getParam("controller");
    	$action = $this->getRequest()->getParam("action");

    	$userAgent = new Zend_Http_UserAgent();
		$device = $userAgent->getDevice();
		$browser = $device->getBrowser();
		$this->config = $conf;
		$this->_helper->viewRenderer->setNoRender();
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: x-requested-with");
		// if($browser=="Internet Explorer"){
	    	header('Content-type: text/javascript');
		// }else{
	 	//	header('Content-type: application/json');
		// }

		if($controller == "login"){
    		$this->user_admin = new Zend_Session_Namespace('admin_auth');
		}else{
	    	$islogin = $this->_helper->Sipitung->checkAdminLogin();
	    	if(!$islogin){
	    		$result["success"] = "false";
	    		$result["reason"] = "login no longer valid";
				echo Zend_Json::encode($result);
				exit;
	    	}else{
	    		$this->user_admin = new Zend_Session_Namespace('admin_auth');
	    	}
	    }
	}
}
?>