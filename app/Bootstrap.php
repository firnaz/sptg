<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	protected function _initView()
    {
		$view = new Zend_View_Smarty($this->getOption('smarty'));
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		$viewRenderer->setView($view)
					 // ->setViewBasePathSpec($view->getEngine()->template_dir)
					 // ->setViewScriptPathSpec(':controller/:action.:suffix')
					 // ->setViewScriptPathNoControllerSpec(':action.:suffix')
					 ->setViewSuffix('phtml');
	}
	protected function _initLoader(){
		$loader = Zend_Loader_Autoloader::getInstance();
		$loader->setFallbackAutoloader(true);
		$loader->suppressNotFoundWarnings(false);
	}

	protected function _initDb()
    {
     	if(!Zend_Registry::isRegistered('db')){
			$config = new Zend_Config_Ini(APPLICATION_PATH . '/config/app.ini', APPLICATION_ENV);
			$adapter = $config->resources->db->adapter;
			$params = $config->resources->db->params;

			$db = Zend_Db::factory($adapter, $params);
 
 			Zend_Db_Table_Abstract::setDefaultAdapter($db);
			Zend_Registry::set('db', $db);
		}else{
			$db = Zend_Registry::get('db');
			Zend_Db_Table_Abstract::setDefaultAdapter($db);
		}
    }

	protected function _initFront(){
		$front = Zend_Controller_Front::getInstance(); 
		$front->registerPlugin(new LayoutPlugins());
		$front->registerPlugin(new AgrPlugins());
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/config/app.ini', APPLICATION_ENV);

		if(!Zend_Registry::isRegistered('config')){
			$db = Zend_Db_Table_Abstract::getDefaultAdapter();

			$data = $db->fetchAll("select * from t_config order by id ASC");

			foreach($data as $key=>$val){
				$conf[$val["nama_variabel"]] = $val["nilai"];
			}
			Zend_Registry::set('config', $conf);
		}else{
			$conf = Zend_Registry::get('config');
		}

		define(ARTIKEL_IMAGES, $config->path->artikel);		
		define(DOKUMEN_FILE, $config->path->dokumen);		
		define(TMP_DIR, $config->path->tmp);
		define(LAYER_THUMBNAIL, $config->path->layer_thumbnail);
		define(PETA_THUMBNAIL, $config->path->peta_thumbnail);
		define(FOTO_MEMBER, $config->path->foto_member);
		define(GAMBAR_PAGES, $config->path->gambar_pages);

		define(GETEPSG, $conf["getepsg"]);
		define(OGR2OGR, $conf["ogr2ogr"]);
		define(GETRASTEREXTENT, $conf["getrasterextent"]);
		define(GETRASTEREPSG, $conf["getrasterepsg"]);
		define(PGSQL_HOST, $config->resources->db->params->host);
		define(PGSQL_USER, $config->resources->db->params->username);
		define(PGSQL_PWD, $config->resources->db->params->password);
		define(PGSQL_DB, $config->resources->db->params->dbname);
		// define(CSW_URL, $config->csw_url);
		Zend_Controller_Action_HelperBroker::addHelper(new Sipitung());
		Zend_Controller_Action_HelperBroker::addHelper(new PyCswClient($conf["csw"]));
		Zend_Controller_Action_HelperBroker::addHelper(new Geoserver($conf["geoserver.url"], $conf["geoserver.workspace"], $conf["geoserver.datastore"], $conf["geoserver.username"], $conf["geoserver.password"]));
		Zend_Controller_Action_HelperBroker::addHelper(new Web());
		Zend_Controller_Action_HelperBroker::addHelper(new Members());
		// $front->addModuleDirectory(APPLICATION_PATH . '/modules');
		// $front->setControllerDirectory(array(
		//     'default' => APPLICATION_PATH.'/controllers',
		//     'api'    => APPLICATION_PATH.'/Api/controllers'
		// ));
	}
	public function _initRouter()
	{	
		$frontController = Zend_Controller_Front::getInstance();
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/config/route.ini');
		$router = $frontController->getRouter();
		$router->addConfig($config,'routes');					
	}
}