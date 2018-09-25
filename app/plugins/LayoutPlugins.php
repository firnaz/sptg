<?php 
class LayoutPlugins extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$layout = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		$view = $layout->view;
    	$module = Zend_Controller_Front::getInstance()->getRequest()->getParam("module");
    	$controller = Zend_Controller_Front::getInstance()->getRequest()->getParam("controller");
    	$action = Zend_Controller_Front::getInstance()->getRequest()->getParam("action");
		$pages['obj'] = ucwords(Zend_Controller_Front::getInstance()->getRequest()->getControllerName());
		$pages['MD5'] = md5(date("YmdHis"));
		$pages['module'] = $module;
		$pages['controller'] = $controller;
		$pages['action'] = $action;
		$pages['config'] = Zend_Registry::get('config');
		$view->_URL=$this->getRequest()->getBaseUrl();
		$view->_FullURL = $this->getRequest()->getScheme()."://".$this->getRequest()->getHttpHost().$this->getRequest()->getBaseUrl();
		$view->pages = $pages;

		$module = $request->getModuleName();
        if ( 'default' !== $module ) {
            $moduleName = $this->toCamelCase($module, true);
            $request->setModuleName($moduleName);
        }
	}
	public static function toCamelCase($str, $capitalise_first_char = false) {
        if($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }
}

?>