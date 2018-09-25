<?php
class Android_PetaController extends Android_Controller_Action
{
    public function init()
    {
        $this->view->page_id = "peta";
        parent::init();
    }

    public function indexAction(){
    	echo "SIH 3 Android API V 1.0.0";
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
                'layer' => $layer['layer'],
                'srs' => $layer['srs'],
            );
        }

        $map_data = array(
            'title'        => $m['judul'],
            'extent'       => array((float) $m['x_min'], (float) $m['y_min'], (float) $m['x_max'], (float) $m['y_max']),
            'layers'       => $layers,
            'abstract'     => $m['deskripsi'],
            'contributor'  => $this->_helper->Members->getMembersName($m['user_creator']),
            'star'         => $m['rating'],
            'description'  => $m['deskripsi'],
            'num_view'     => $m['num_view']
        );

        echo json_encode(array("success"=>true, "data"=>$map_data));
	}

	public function listAction(){
        $page = $this->getRequest()->getParam("pages");
        $limit = 12;
        $start = ($page>0)?(($page-1)*$limit):0;
        $m = $this->_helper->Web->getPeta("");
        $map = array();
        $conf = Zend_Registry::get('config');
        $fullUrl = dirname($conf["csw"]);
        foreach($m["rows"] as $key=>$val){
            // $layers = $val["layers"];
            // $l = array();
            // foreach($layers as $key1=>$val1){
            //     $l[] = array("url"=>$val1["url"],"layer"=>$val1["layer"]);
            // }
            // print_r($val);exit;
            $map[] = array(
            	'id'		=> $val["id"],
                'title'     => $val["judul"],
                'url'       => $fullUrl . '/android/peta/detail/' . $val['url_segment'],
                'deskripsi' => $val["deskripsi"],
                'thumbnail' => $fullUrl . '/images/peta/thumbnail/200/200/peta-'.$val["id"].'.png',
                'contributor'=> $val["user_creator"]
            );
        }
		echo json_encode(array("data"=>$map, "success"=>true));		
	}

	public function simpulAction(){
		$c = new Children();
        $children = $this->_helper->Web->getChildren();

		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$total = $db->fetchRow($db->select()->from(array('t_peta_gallery'),array('count(*) as total')));


        $children_res = array();
        $conf = Zend_Registry::get('config');
        $fullUrl = dirname($conf["csw"]);
        $children_res[] = array(
        	'id' 		=> 0,
            'title'    => "BMKG Pusat",
            'url'      => $fullUrl. '/android/peta/list',
            'total'		=>$total["total"]
        );
        if(count($children)>0){
            foreach($children as $child){
				$datachild = $c->fetchRow("id='".$child["id"]."'");
				$result = $this->query($datachild->url_api."/peta/view",12,$this->childCookies[$child["id"]]);
				if($result){
					// $r = json_decode($result);
	                $children_res[] = array(
	                	'id' => $child["id"],
	                    'title'    => $child["nama_sistem"],
	                    'url'      => trim($child["url_publik"],"/"). '/android/peta/list',
	                    'total'	=> $result->total
	                );
	            }
            }
        }
        echo json_encode(array("data"=>$children_res, "success"=>true));
	}

}
?>