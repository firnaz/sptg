<?php
class Api_ConfigController extends Api_Controller_Action{
	public function viewAction()
	{
		extract($this->getRequest()->getParams());
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if(!$sort) { 
			$dir = 'ASC'; 
			$sort='id';
		}else{
			$order = json_decode($sort);
			$dir = $order[0]->direction;
			$sort = $order[0]->property;
		}	
		
		$config = new Config();
		$data = $config->fetchAll($config->select()->order("$sort $dir"));

		$total = $db->fetchRow($db->select()->from(array('t_config'),array('count(*) as total')));
		$data = count($data)?$data->toArray():array();
		
		$i=0;
		foreach ($data as $key=>$val){
			$row[$i]["id"] = $val["id"];
			$row[$i]["variable"] = $val["nama_variabel"];
			$row[$i]["name"] = $val["keterangan"];
			$row[$i]["value"] = $val["nilai"];
			$i++;
		}	
		
		echo json_encode(array("rows"=>$row, "total"=>$total["total"]));	
	}
	public function saveAction(){
		// $this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		extract($this->getRequest()->getParams());

		$values = json_decode($gridvalues);
		foreach($values as $key=>$val){
			$sql = "update t_config set nilai='".$val."' where nama_variabel='".$key."'";
			$db->query($sql);
		}

		$result["success"] = "true";
		echo Zend_Json::encode($result);
	}
}
?>