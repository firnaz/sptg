<?php
class Member_IndexController extends Member_Controller_Action
{
    public function init(){
        $this->view->page_id = "dashboard";
        parent::init();
    }
    public function indexAction()
    {
        $this->view->member_name = $this->member_auth->nama;
        $limit = 10;
        // $layers = $this->_helper->Members->getMemberLayers($limit, 0);
        $layers = $this->_helper->Web->getLayerList('', $limit, 0);
        $layers_res = array();
        if($layers['rows'])
        foreach($layers['rows'] as $layer){
            $layers_res[] = array(
                'title'      => $layer['title'],
                'thumb'      => $this->view->_URL . '/images/layer/thumbnail/218/218/' . $layer['thumbnail'],
                'author'     => $this->_helper->Members->getMembersName($layer['contributor']),
                'star'       => $layer['rating'],
                'view_count' => $layer['num_view'],
                'url'        => $this->view->_URL . '/member/layer/detail/' . $layer['identifier']
            );
        }
        $this->view->layers = $layers_res;

        $limit = 1;
        $maps = $this->_helper->Web->getPeta('', $limit, 0);
        $maps_res = array();
        if($maps['rows'])
        foreach($maps['rows'] as $map){
            $maps_res[] = array(
                'title'      => $map['judul'],
                'thumb'      => $this->view->_URL . '/images/peta/thumbnail/200/200/peta-'.$map["id"].'.png',
                'author'     => $this->_helper->Members->getMembersName($map['user_creator']),
                'star'       => $map['rating'],
                'view_count' => $map['num_view'],
                'url'        => $this->view->_URL . '/member/peta/detail/' . $map['url_segment']
            );
        }
        $this->view->maps = $maps_res;


        $limit = 10;
        $documents = $this->_helper->Web->getDocs('', $limit, 0);
        $documents_res = array();
        if($documents['rows'])
        foreach($documents['rows'] as $document){
            $documents_res[] = array(
                'title'      => $document['judul'],
                'url'        => $this->view->_URL . '/member/dokumen/detail/' . $document['id']
            );
        }
        $this->view->documents = $documents_res;

	}
	public function loginAction(){
    	$username = $this->getRequest()->getParam("username");
    	$password = $this->getRequest()->getParam("password");
    	//auth
    	if($username && $password){
	    	$result = $this->_helper->Members->login($username,$password);
	    	if($result["success"]){
				$this->_redirect('/member');
	    	}else{
	    		$this->view->message = $result["reason"];
	    	}
    	}
	}
	public function logoutAction(){
	   	$result = $this->_helper->Members->logout();
		$this->_redirect('/member/login');
	}
}
?>