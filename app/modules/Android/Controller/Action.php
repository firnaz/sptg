<?php
class Android_Controller_Action extends Zend_Controller_Action
{
	protected $childCookies;
	public function init(){
		$this->childCookies = Zend_Registry::get('childCookies');
    	$controller = $this->getRequest()->getParam("controller");
    	$action = $this->getRequest()->getParam("action");

  //   	$userAgent = new Zend_Http_UserAgent();
		// $device = $userAgent->getDevice();
		// $browser = $device->getBrowser();
		$this->config = $conf;
		$this->_URL=$this->getRequest()->getBaseUrl();
		$this->_FullURL = $this->getRequest()->getScheme()."://".$this->getRequest()->getHttpHost().$this->getRequest()->getBaseUrl();
		$this->_helper->viewRenderer->setNoRender();
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: x-requested-with");
		if($browser=="Internet Explorer"){
	    	header('Content-type: text/javascript');
		}else{
	    	header('Content-type: application/json');
		}
	}
	public function query($uri, $limit, $cookiejar){
		$url = $uri."?start=0&limit=".$limit;
        $client = new Zend_Http_Client();
		$client->setConfig(array('keepalive'=>true));
        $client->setCookieJar($cookiejar);
        $client->setUri($url);
        $client->setParameterPost($params);                

        $response = $client->request(Zend_Http_Client::POST);
        $output = json_decode($response->getBody());
        if($output->success=="false"){
	        return false;        	
        }else{
	        return $output;
        }
	}
}
?>