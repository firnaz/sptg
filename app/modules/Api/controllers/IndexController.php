<?php
class Api_IndexController extends Api_Controller_Action
{
    public function indexAction()
    {
    	echo "SIH 3 API V 1.0";
	}
	public function testcswAction(){
		print_r($this->_helper->PyCswClient->getRecords());
	}
}
?>