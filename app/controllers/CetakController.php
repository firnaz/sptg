<?php
class CetakController extends Zend_Controller_Action
{
    private $judul;
    private $minx;
    private $miny;
    private $maxx;
    private $maxy;
    private $extent;
    private $layers;
    public function init()
    {
        $this->view->page_id = "cetak";
        $params              = $this->getRequest()->getParams();
        // print_r($params);exit;
        $this->judul         = $params["judul"];
        $this->scale         = $params["scale"];
        $this->extent        = $params["extent"];
        $this->layers        = $params["layers"];
        parent::init();
    }
    public function indexAction(){
        $conf = Zend_Registry::get('config');
        $legend = array();

        // generate lenged
        $i = 0;
        $j = 0;
        if (!is_array($this->layers)){
            $layers = json_decode($this->layers,true);
        }
        if(!is_array($this->extent)){
            $extent = json_decode($this->extent,true);            
        }

        if($layers)
        foreach($layers as $layer){
            $question = (preg_match('/\?/', $layer['url'])) ? '' : '?';
            if(!$legend[$j]){
                $legend[$j] = array();
            }
            $legend[$j][$i]['title'] = $layer['title'];
            $legend[$j][$i]['url'] = $layer['url'] . $question . 'REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&WIDTH=20&HEIGHT=20&LEGEND_OPTIONS=forceRule:True;dx:0.2;dy:0.2;mx:0.2;my:0.2;border:false;fontColor:333333;fontSize:12&LAYER=' . $layer['params']['LAYERS'];
            if($i > 6){
                $i = 0;
                $j++;
            }else{
                $i++;
            }
        }

        $this->view->data = array(
            'judul' => $this->judul,
            'sumber' => array(
                'nama' => $conf["app_name"],
                'url' => $this->view->_FullURL
            ),
            'scale' => number_format(round($this->scale,-3),0,",","."),
            'legend' => $legend,
            'layers' => $this->layers,
            'extent' => $this->extent
        );
    }
    public function printAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $conf = Zend_Registry::get('config');
        if (is_array($this->layers)){
            $layers = json_encode($this->layers);
        }
        if(is_array($this->extent)){
            $extent = json_encode($this->extent);
        }
        $outputfile = "/tmp/".md5($this->judul.date('YmdHis')).".pdf";
        $url = $this->view->_FullURL."/cetak?judul=".urlencode($this->judul)."&scale=".urlencode($this->scale)."&extent=".urlencode($extent)."&layers=".urlencode($layers);
        $cmd = $conf["phantomjs"]." '".APPLICATION_PATH."/plugins/html2pdf.js' '".$url."' $outputfile";
        $result = exec($cmd);

        // It will be called downloaded.pdf
        if(file_exists($outputfile)){
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$this->judul.'.pdf"');
            readfile($outputfile);
            @unlink($outputfile);
        }
	}
}
?>