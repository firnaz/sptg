<?php
class ProfilController extends Zend_Controller_Action
{
    public function init(){
        $this->view->page_id = "profil";
        parent::init();
    }
    public function indexAction()
    {
		$pages = new Pages();
		$rows = $pages->fetchAll($pages->select()->where("nama_page='profil'")->limit(1,0));
		$rows = $rows->toArray();
		$this->view->m = $rows[0];
	}
}
?>