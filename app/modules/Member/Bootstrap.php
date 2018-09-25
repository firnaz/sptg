<?php
class Member_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initModulePath(){
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'basePath' => APPLICATION_PATH.'/modules/Member',
            'namespace' => 'Member_',
            'resourceTypes' => array(
                'Controller' => array(
                    'path' => 'Controller/',
                    'namespace' => 'Controller',
                ),
                'helper' => array(
                    'path' => 'helper/',
                    'namespace' => 'Helper',
                )
            )
        ));
    }
    protected function _initModuleRouter(){
		$frontController = Zend_Controller_Front::getInstance();
		$config = new Zend_Config_Ini(APPLICATION_PATH.'/modules/Member/config/route.ini');
		$router = $frontController->getRouter();
		$router->addConfig($config,'routes');
    }
}