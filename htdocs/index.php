<?php
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../app'));
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(APPLICATION_PATH . '/../libs'),
	get_include_path(),
)));

error_reporting(E_ALL & ~E_NOTICE);

require_once 'Zend/Application.php';
require_once 'Smarty/Smarty.class.php';

$application = new Zend_Application(APPLICATION_ENV,
	APPLICATION_PATH . '/config/app.ini'
);

$application->bootstrap()->run();