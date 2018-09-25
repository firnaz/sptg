<?php
class Member_PetaController extends Member_Controller_Action
{
    public function init(){
        $this->view->page_id = "peta";
        parent::init();
    }
    public function indexAction()
    {
        // filter
        $this->view->filter_sort     = ($_GET['filter_sort']) ? $_GET['filter_sort'] : 'terbaru';
        $this->view->filter_author   = ($_GET['filter_author']) ? $_GET['filter_author'] : 'all';

        $this->view->filter_min_x       = ($_GET['filter_min_x']) ? (float) $_GET['filter_min_x'] : 70;
        $this->view->filter_min_y       = ($_GET['filter_min_y']) ? (float) $_GET['filter_min_y'] : -30;
        $this->view->filter_max_x       = ($_GET['filter_max_x']) ? (float) $_GET['filter_max_x'] : 170;
        $this->view->filter_max_y       = ($_GET['filter_max_y']) ? (float) $_GET['filter_max_y'] : 30;

        $this->view->filter_center_x    = ($_GET['filter_center_x']) ? (float) $_GET['filter_center_x'] : 117.5;
        $this->view->filter_center_y    = ($_GET['filter_center_y']) ? (float) $_GET['filter_center_y'] : 0;
        $this->view->filter_center_zoom = ($_GET['filter_center_zoom']) ? (float) $_GET['filter_center_zoom'] : 2;

        $this->view->filter_text     = ($_GET['filter_keyword']) ? $_GET['filter_keyword'] : '';

        $this->view->complete_filter = 'filter_min_x='.$this->view->filter_min_x.'&filter_min_y='.$this->view->filter_min_y.'&filter_max_x='.$this->view->filter_max_x.'&filter_max_y='.$this->view->filter_max_y.'&filter_keyword='.$this->view->filter_keyword;

        $limit = 6;
        $page = $this->getRequest()->getParam("pages");
        $start = ($page>0)?(($page-1)*$limit):0;
        $where = '1=1';
        if($this->view->filter_author=="me"){
            $where .= " AND user_creator='".$this->member_auth->username."'";
        }
        if(trim($this->view->filter_text)){
            $where .= $this->db->quoteInto(" AND (LOWER(judul) LIKE LOWER(?) OR LOWER(deskripsi) LIKE LOWER(?))", "%".trim($this->view->filter_text)."%", "%".trim($this->view->filter_text)."%");
        }
        $max_x = $this->view->filter_max_x<0?180:$this->view->filter_max_x;
        if($this->view->filter_min_x && $this->view->filter_min_y && $this->view->filter_max_x && $this->view->filter_max_y){
            $where .= " AND ST_Contains(ST_GeomFromText('POLYGON((".$this->view->filter_min_x." ".$this->view->filter_min_y.", ".$max_x." ".$this->view->filter_min_y.", ".$max_x." ".$this->view->filter_max_y.", ".$this->view->filter_min_x." ".$this->view->filter_max_y.", ".$this->view->filter_min_x." ".$this->view->filter_min_y."))'), ST_GeomFromText('POLYGON((' || x_min || ' ' || y_min || ', ' || x_max || ' ' || y_min || ', ' || x_max || ' ' || y_max || ', ' || x_min || ' '|| y_max || ', ' || x_min || ' ' || y_min || '))'))";
        }
        // echo $where;exit;

        $order = "time_created DESC";
        if($this->view->filter_sort =="terpopuler"){
            $order = "num_view DESC";
        }elseif($this->view->filter_sort =="terpilih"){
            $order = "bintang DESC";
        }

        $maps = $this->_helper->Web->getPeta($where, $limit, $start, $order);

        $maps_res = array();
        if($maps['rows'])
        foreach($maps['rows'] as $peta){
            $maps_res[] = array(
                'title'      => $peta['judul'],
                'thumb'      => $this->view->_URL . '/images/peta/thumbnail/200/200/peta-'.$peta["id"].'.png?'.md5(uniqid()),
                'author'     => $this->_helper->Members->getMembersName($peta['user_creator']),
                'star'       => $peta['rating'],
                'view_count' => $peta['num_view'],
                'url'        => ($peta['user_creator']==$this->member_auth->username)?$this->view->_URL . '/member/peta/edit/' . $peta['url_segment'] : $this->view->_URL . '/member/peta/detail/' . $peta['url_segment'],
                'mode' =>  ($peta['user_creator']==$this->member_auth->username)? 'edit' : 'view'
            );
        }
        $this->view->maps = $maps_res;
        $pages = $maps['pages'];
        $this->view->pages = $pages;
	}
    public function uploadAction()
    {
        // klo di klik simpan, ajax post kesini
        if($_POST){
            // print_r($_POST);exit;
            $params = $this->getRequest()->getParams();
            $judul = $params["info"]["judul"];
            $abstract = $params["info"]["abstract"];
            $xmin = $params["extent"]["minx"];
            $ymin = $params["extent"]["miny"];
            $xmax = $params["extent"]["maxx"];
            $ymax = $params["extent"]["maxy"];
            $layers = $params["layers"];

            $result = $this->_helper->Members->addMemberPeta($judul, $abstract, $xmin, $ymin, $xmax, $ymax, $layers);
            // save data

            // trus klo mau redirect gini
            echo json_encode(array(
                'redirect' => $this->view->_URL . '/member/peta'
            ));
            exit;
        }
        $this->view->listWMS = $this->_helper->Web->getWmsLayer();
    }
    public function detailAction()
    {
        $url_segment = $this->getRequest()->getParam("url_segment");

        $m = $this->_helper->Web->getPetaByURLSegment($url_segment);
        $layers = array();

        if($m['layers'])
        foreach($m['layers'] as $layer){
            $layers[] = array(
                'title' => $layer['title'],
                'url'   => $layer['url'],
                'layer' => $layer['layer']
            );
        }

        $this->view->map_data = array(
            'title'       => $m['judul'],
            'extent'      => array((float) $m['x_min'], (float) $m['y_min'], (float) $m['x_max'], (float) $m['y_max']),
            'layers'      => $layers,
            'abstract'    => $m['deskripsi'],
            'contributor' => $this->_helper->Members->getMembersName($m['user_creator']),
            'star'        => $m['rating'],
            'num_view'    => $m['num_view'],
        );
        $this->_helper->Web->updatePetaViews($url_segment);
    }
    public function editAction()
    {
        $url_segment = $this->getRequest()->getParam("url_segment");
        $this->view->listWMS = $this->_helper->Web->getWmsLayer();

        $m = $this->_helper->Web->getPetaByURLSegment($url_segment);
        // klo di klik simpan, ajax post kesini
        if($_POST){
            // print_r($_POST);exit;
            $params = $this->getRequest()->getParams();
            $isdelete = $params["delete"];
            if($isdelete){
                $result = $this->_helper->Members->deleteMemberPeta($m["id"]);
                echo json_encode(array(
                    'redirect' => $this->view->_URL . '/member/peta'
                ));
            }else{
                $judul = $params["info"]["judul"];
                $abstract = $params["info"]["abstract"];
                $xmin = $params["extent"]["minx"];
                $ymin = $params["extent"]["miny"];
                $xmax = $params["extent"]["maxx"];
                $ymax = $params["extent"]["maxy"];
                $layers = $params["layers"];
                $result = $this->_helper->Members->updateMemberPeta($m["id"], $judul, $abstract, $xmin, $ymin, $xmax, $ymax, $layers);
                echo json_encode(array(
                    'redirect' => $this->view->_URL . '/member/peta/edit/'.$url_segment
                ));
            }
            exit;
        }
        if($m["user_creator"]!=$this->member_auth->username){
            $this->_redirect('/member/peta/detail/'.$url_segment);
            exit;
        }
        $layers = array();

        if($m['layers'])
        foreach($m['layers'] as $layer){
            $layers[] = array(
                'identifier'   => $layer['identifier'],
                'title'        => $layer['title'],
                'url'          => $layer['url'],
                'layer'        => $layer['layer'],
                'abstract'     => $m['deskripsi'],
                'star'         => $m['rating'],
                'num_view'     => $m['num_view'],
            );
        }

        $this->view->map_data = array(
            'title'  => $m['judul'],
            'abstract' => $m['deskripsi'],
            'extent' => array((float) $m['x_min'], (float) $m['y_min'], (float) $m['x_max'], (float) $m['y_max']),
            'layers' => $layers,
            'url_segment' => $url_segment,
            'abstract'    => $m['deskripsi'],
            'contributor' => $this->_helper->Members->getMembersName($m['user_creator']),
            'star'        => $m['rating'],
            'num_view'    => $m['num_view'],
        );
    }
    public function lokallayerAction(){
        $layers = $this->_helper->Web->getLayers("", 99999, 0, "title ASC");
        $result = array(
            'url' => null,
            'layers' => null
        );
        if($layers['rows'])
        foreach( $layers['rows'] as $layer ){
            $result['url'] = $layer['url'];
            $result['layers'][] = array(
                'identifier' => $layer['identifier'],
                'title' => $layer['title'],
                'layer' => $layer['layer'],
            );
        }
        $this->_helper->Web->json_encode($result);
    }
}
?>