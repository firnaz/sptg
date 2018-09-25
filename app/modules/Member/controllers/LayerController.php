<?php
class Member_LayerController extends Member_Controller_Action
{
    public function init(){
        $this->view->page_id = "layer";
        parent::init();
    }
    public function indexAction()
    {
        // filter
        $this->view->filter_sort        = ($_GET['filter_sort']) ? $_GET['filter_sort'] : 'terbaru';
        $this->view->filter_author      = ($_GET['filter_author']) ? $_GET['filter_author'] : 'all';

        $this->view->filter_min_x       = ($_GET['filter_min_x']) ? (float) $_GET['filter_min_x'] : 70;
        $this->view->filter_min_y       = ($_GET['filter_min_y']) ? (float) $_GET['filter_min_y'] : -30;
        $this->view->filter_max_x       = ($_GET['filter_max_x']) ? (float) $_GET['filter_max_x'] : 170;
        $this->view->filter_max_y       = ($_GET['filter_max_y']) ? (float) $_GET['filter_max_y'] : 30;

        $this->view->filter_center_x    = ($_GET['filter_center_x']) ? (float) $_GET['filter_center_x'] : 117.5;
        $this->view->filter_center_y    = ($_GET['filter_center_y']) ? (float) $_GET['filter_center_y'] : 0;
        $this->view->filter_center_zoom = ($_GET['filter_center_zoom']) ? (float) $_GET['filter_center_zoom'] : 2;

        $this->view->filter_text        = ($_GET['filter_keyword']) ? $_GET['filter_keyword'] : '';
        $this->view->filter_raster      = ($_GET['filter_raster']) ? $_GET['filter_raster'] : 'false';
        $this->view->filter_vector      = ($_GET['filter_vector']) ? $_GET['filter_vector'] : 'false';

        $this->view->complete_filter    = 'filter_min_x='.$this->view->filter_min_x.'&filter_min_y='.$this->view->filter_min_y.'&filter_max_x='.$this->view->filter_max_x.'&filter_max_y='.$this->view->filter_max_y.'&filter_keyword='.$this->view->filter_keyword.'&filter_raster='.$this->view->filter_raster.'&filter_vector='.$this->view->filter_vector;

        $this->view->vector_count = $this->_helper->Web->getLayerCount('vektor');
        $this->view->raster_count = $this->_helper->Web->getLayerCount('raster');;

        $limit = 6;
        $page = $this->getRequest()->getParam("pages");
        $start = ($page>0)?(($page-1)*$limit):0;
        $where = '1=1';
        if($this->view->filter_author=="me"){
	        $where .= " AND contributor='".$this->member_auth->username."'";
        }
        if($this->view->filter_raster=="true" && $this->view->filter_vector=="false"){
        	$where .= " AND tipe_layer='raster'";
        }elseif ($this->view->filter_vector=="true" && $this->view->filter_raster=="false"){
        	$where .= " AND tipe_layer='vektor'";
        }
        if(trim($this->view->filter_text)){
        	$where .= $this->db->quoteInto(" AND lower(anytext) LIKE lower(?)", "%".trim($this->view->filter_text)."%");
        }
        $max_x = $this->view->filter_max_x<0?180:$this->view->filter_max_x;
        if($this->view->filter_min_x && $this->view->filter_min_y && $this->view->filter_max_x && $this->view->filter_max_y){
            $where .= " AND ST_Contains(ST_GeomFromText('POLYGON((".$this->view->filter_min_y." ".$this->view->filter_min_x.", ".$this->view->filter_min_y." ".$max_x.", ".$this->view->filter_max_y." ".$max_x.", ".$this->view->filter_max_y." ".$this->view->filter_min_x.", ".$this->view->filter_min_y." ".$this->view->filter_min_x."))'), ST_GeomFromText(wkt_geometry))";
	    }
    	$order = "date_modified DESC";
	    if($this->view->filter_sort=="terpopuler"){
	    	$order = "num_view DESC";
	    }elseif($this->view->filter_sort=="terpilih"){
	    	$order = "bintang DESC";
	    }
        // echo $where;exit;
        $layers = $this->_helper->Web->getLayers($where, $limit, $start, $order);

        $layers_res = array();
        if($layers['rows'])
        foreach($layers['rows'] as $layer){
            $layers_res[] = array(
                'title'      => $layer['title'],
                'thumb'      => $this->view->_URL . '/images/layer/thumbnail/218/218/' . $layer['thumbnail']. '?' . md5(uniqid()),
                'author'     => $this->_helper->Members->getMembersName($layer['contributor']),
                'star'       => $layer['rating'],
                'view_count' => $layer['num_view'],
                // 'url'        => $this->view->_URL . '/member/layer/detail/' . $layer['identifier']
                'url' =>  ($layer['contributor']==$this->member_auth->username)?$this->view->_URL . '/member/layer/edit/' . $layer['identifier'] : $this->view->_URL . '/member/layer/detail/' . $layer['identifier'],
                'mode' =>  ($layer['contributor']==$this->member_auth->username)? 'edit' : 'view'
            );
        }
        // print_r($layers);exit;
        $this->view->layers = $layers_res;
        $pages = $layers['pages'];
        $this->view->pages = $pages;
	}

    public function detailAction(){
        $identifier = $this->getRequest()->getParam("identifier");
        $layer = $this->_helper->Web->getLayerByIdentifier($identifier);
        $this->view->layer = array(
            'title'       => $layer['title'],
            'abstract'    => $layer['abstract'],
            'contributor' => $this->_helper->Members->getMembersName($layer['contributor']),
            'star'        => $layer['rating'],
            'num_view'    => $layer['num_view'],
            'url'         => $layer['url'],
            'layer'       => $layer['layer'],
            'extent'      => array((float) $layer['xmin'], (float) $layer['ymin'], (float) $layer['xmax'], (float) $layer['ymax'])
        );
        $this->_helper->Web->updateLayerViews($identifier);
    }

    public function editAction(){

        $identifier = $this->getRequest()->getParam("identifier");
        $layer = $this->_helper->Web->getLayerByIdentifier($identifier);
        if($layer["contributor"]!=$this->member_auth->username){
            $this->_redirect('/member/layer/detail/'.$identifier);
            exit;
        }

        if($_POST){
            $isdelete = $this->getRequest()->getParam("delete");
            if($isdelete){
                $result = $this->_helper->Members->deleteMemberLayer($identifier);
                $redirect = $this->view->_URL . '/member/layer';
            }else{
                $title = $this->getRequest()->getParam("judul");
                $abstract = $this->getRequest()->getParam("abstract");
                if (is_array($this->getRequest()->getParam("categories"))){
                    foreach($this->getRequest()->getParam("categories") as $id_kategori){
                        $kat = $this->_helper->Web->getLayerCategory($id_kategori);
                        $subject[] = $kat["nama_kategori"];
                    }
                }else{
                    $kat = $this->_helper->Web->getLayerCategory($this->getRequest()->getParam("category"));
                    $subject[] = $kat["nama_kategori"];
                }
                $result = $this->_helper->Members->updateMemberLayer($identifier, $title, $abstract, $subject);
                $redirect = $this->view->_URL . '/member/layer/edit/'.$identifier;
            }
            if ($result["success"]){
                echo json_encode(array(
                    'redirect' => $redirect
                ));
            }else{
                echo json_encode(array(
                    'message' => $result["reason"]
                ));
            }
            exit;                
        }

        $this->view->layer = array(
            'title'       => $layer['title'],
            'identifier'  => $identifier,
            'abstract'    => $layer['abstract'],
            'contributor' => $this->_helper->Members->getMembersName($layer['contributor']),
            'star'        => $layer['rating'],
            'num_view'    => $layer['num_view'],
            'url'         => $layer['url'],
            'layer'       => $layer['layer'],
            'extent'      => array((float) $layer['xmin'], (float) $layer['ymin'], (float) $layer['xmax'], (float) $layer['ymax'])
        );
    }

    public function uploadAction(){
    	$params = $this->getRequest()->getParams();
    	$message = "";
    	// print_r($params);exit;
    	if ($params["submit"]){
	    	if (trim($params["judul"]) && $params["tipe"] && count($params["categories"])>0 && $_FILES['file']["size"]>0){
	    		$title = $params["judul"];
	    		$abstract = $params["abstract"];
	    		if ($params["tipe"]=="vector"){
	    			$tipe_layer="vektor";
	    		}else{
	    			$tipe_layer="raster";
	    		}
	    		$subject = array();
	    		if (is_array($params["categories"])){
	    			foreach($params["categories"] as $id_kategori){
	    				$kat = $this->_helper->Web->getLayerCategory($id_kategori);
	    				$subject[] = $kat["nama_kategori"];
	    			}
	    		}else{
					$kat = $this->_helper->Web->getLayerCategory($params["category"]);
					$subject[] = $kat["nama_kategori"];
	    		}
	    		$action = $this->_helper->Members->addMemberLayers($title, $abstract, $subject, $tipe_layer);
	    		if (!$action["success"]){
	    			$message.=$action["reason"];
                    $this->_redirect("member/layer");
	    		}else{
	    			$message.="Layer berhasil ditambahkan.";
	    		}
	    	}else{
	    		if(!trim($params["judul"])){
	    			$message .= "Judul harus diisi dengan benar.<br/>";
	    		}
	    		if(!trim($params["tipe"])){
	    			$message .= "Anda harus memilih tipe layer.<br/>";
	    		}
	    		if(count($params["categories"])==0){
	    			$message .= "Anda harus memilih Kategori.<br/>";
	    		}
	    		if($_FILES['file']["size"]>0){
	    			$message .= "Anda belum mengisi file..<br/>";
	    		}
	    	}
	    }
    	$c = $this->_helper->Web->getLayerCategories();

    	$categories_res = array();
    	foreach($c as $category){
    		$categories_res[] = array("value"=>$category["id"], "name"=>$category["nama_kategori"]);
    	}
        $this->view->categories = $categories_res;
    }
}
?>