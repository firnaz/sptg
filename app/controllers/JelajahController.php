<?php
class JelajahController extends Zend_Controller_Action
{
    public function init(){
        $this->view->page_id = "jelajah";
        parent::init();
    }
    public function indexAction()
    {
        $this->view->listWMS = $this->_helper->Web->getWmsLayer();
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