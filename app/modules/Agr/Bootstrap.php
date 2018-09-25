<?php
class Agr_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initModulePath(){
        $autoloader = new Zend_Application_Module_Autoloader(
            array(
                'basePath' => APPLICATION_PATH.'/modules/Agr',
                'namespace' => 'Agr_',
                'resourceTypes' => array(
                    'Controller' => array(
                        'path' => 'Controller/',
                        'namespace' => 'Controller',
                    )
                )
            )
        );
    }
    protected function _initModuleRouter(){
		$frontController = Zend_Controller_Front::getInstance();
		$config = new Zend_Config_Ini(APPLICATION_PATH.'/modules/Agr/config/route.ini');
		$router = $frontController->getRouter();
		$router->addConfig($config,'routes');
    }
    // protected function _initChildren(){
    //     if(!Zend_Registry::isRegistered('childCookies')){
    //         $db = Zend_Db_Table_Abstract::getDefaultAdapter();

    //         $data = $db->fetchAll("select * from t_client order by id ASC");

    //         foreach($data as $key=>$val){
    //             $uri = $val["url_api"]."/login";
    //             $params = array('username' => trim($val["username"]), 'password' => trim($val["password"]));
    //             $client = new Zend_Http_Client();
    //             $client->setCookieJar();
    //             $client->setUri($uri);
    //             $client->setParameterPost($params);                

    //             $response = $client->request(Zend_Http_Client::POST);
    //             $output = json_decode($response->getBody());

    //             if($output->success=="true"){
    //                 $children[$val['id']] = $client->getCookieJar(); 
    //             }
    //         }

    //         Zend_Registry::set('childCookies', $children);
    //     }
    // }
}