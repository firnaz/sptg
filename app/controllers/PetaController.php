<?php
class PetaController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->page_id = "peta";
        parent::init();
    }
    public function indexAction()
    {
        $page = $this->getRequest()->getParam("pages");
        $limit = 12;
        $start = ($page>0)?(($page-1)*$limit):0;
        $m = $this->_helper->Web->getPeta("", $limit, $start);
        $map = array();
        foreach($m["rows"] as $key=>$val){
            $layers = $val["layers"];
            $l = array();
            foreach($layers as $key1=>$val1){
                $l[] = array("url"=>$val1["url"],"layer"=>$val1["layer"]);
            }
            $map[] = array(
                'title'     => $val["judul"],
                'url'       => $this->view->_URL . '/peta/detail/' . $val['url_segment'],
                'deskripsi' => $val["deskripsi"],
                'thumbnail' => $this->view->_URL . '/images/peta/thumbnail/200/200/peta-'.$val["id"].'.png'
            );
        }
        $this->view->map = $map;
        $this->view->pages = $m['pages'];

        $children = $this->_helper->Web->getChildren();
        if(count($children)>0){
            $children_res = array();
            foreach($children as $child){
                $children_res[] = array(
                    'title'    => $child["nama_sistem"],
                    'ajaxurl'  => 'agr/'.$this->view->page_id.'/'.$child["id"],
                    'url'      => $this->view->page_id.'/simpul/'.$child["id"],
                    'selected' => false
                );
            }

            $conf = Zend_Registry::get('config');
            $this->view->source_switcher = array_merge(array(
                array(
                    'title'    => $conf["app_name"],
                    'url'      => $this->view->page_id,
                    'selected' => true
                )),
                $children_res
            );
        }
	}
    public function detailAction(){
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

        $this->view->map_data = array(
            'title'        => $m['judul'],
            'extent'       => array((float) $m['x_min'], (float) $m['y_min'], (float) $m['x_max'], (float) $m['y_max']),
            'layers'       => $layers,
            'abstract'     => $m['deskripsi'],
            'contributor'  => $this->_helper->Members->getMembersName($m['user_creator']),
            'star'         => $m['rating'],
            'description'  => $m['deskripsi'],
            'num_view'     => $m['num_view']
        );
        $this->_helper->Web->updatePetaViews($url_segment);
    }
    public function simpulAction(){
        $id_simpul = $this->getRequest()->getParam("id");
        $children = $this->_helper->Web->getChildren();
        if(count($children)){
            $children_res = array();
            foreach($children as $child){
                if($id_simpul==$child["id"]){
                    $children_active = array(
                        'title'    => $child["nama_sistem"],
                        'ajaxurl'  => 'agr/'.$this->view->page_id.'/'.$child["id"],
                        'url'      => $this->view->page_id.'/simpul/'.$child["id"],
                        'selected' => true
                    );
                    $children_res[] = $children_active;
                }else{
                    $children_res[] = array(
                        'title'    => $child["nama_sistem"],
                        'ajaxurl'  => 'agr/'.$this->view->page_id.'/'.$child["id"],
                        'url'      => $this->view->page_id.'/simpul/'.$child["id"],
                        'selected' => false
                    );
                }
            }

            $conf = Zend_Registry::get('config');
            $this->view->source_switcher = array_merge(array(
                array(
                    'title'    => $conf["app_name"],
                    'url'      => 'peta',
                    'selected' => false
                )),
                $children_res
            );
        }else{
            $this->_redirect($this->view->page_id);
        }
        $this->view->children_active = $children_active;
    }
}
?>