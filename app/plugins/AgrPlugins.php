<?php 
class AgrPlugins extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        if ('agr' != $request->getModuleName()) {
            // If not in this module, return early
            return;
        }
        if(!Zend_Registry::isRegistered('childCookies')){
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();

            $data = $db->fetchAll("select * from t_client order by id ASC");

            foreach($data as $key=>$val){
                $uri = $val["url_api"]."/login";
                $params = array('username' => trim($val["username"]), 'password' => trim($val["password"]));
                $client = new Zend_Http_Client();
                $client->setCookieJar();
                $client->setUri($uri);
                $client->setParameterPost($params);                

                $response = $client->request(Zend_Http_Client::POST);
                $output = json_decode($response->getBody());

                if($output->success=="true"){
                    $children[$val['id']] = $client->getCookieJar(); 
                }
            }

            Zend_Registry::set('childCookies', $children);
        }
    }
}

?>