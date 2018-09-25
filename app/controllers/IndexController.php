<?php
class IndexController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->page_id = "home";
        parent::init();
    }

    public function indexAction()
    {
        $m = $this->_helper->Web->getPeta("", 10, 0);
        $map = array();

        foreach ($m["rows"] as $key=>$val) {
            $layers = $val["layers"];
            $l = array();
            foreach ($layers as $key1=>$val1) {
                if (strlen($val1["url"]) && strlen($val1["layer"])) {
                    $l[] = array("url"=>$val1["url"],"layer"=>$val1["layer"]);
                }
            }
            if ($l) {
                $map[] = array(
                        'title' => $val["judul"],
                        'extent' => array((float) $val["x_min"], (float) $val["y_min"], (float) $val["x_max"], (float) $val["y_max"]),
                        'layers' => $l
                    );
            }
        }

        $this->view->map = $map;
        $this->view->intro = array(
            'header'   => 'Menyajikan Informasi Hidrometeorologi',
            'content'  => 'Clearinghouse Hidrometeorologi merupakan salah satu simpul Sistem Informasi Hidrologi, Hidrometeorologi dan Hidrogeologi (SIH3). Data dan informasi hidrometeorolgi disajikan dan divisualisasikan dalam bentuk galeri peta.',
            'services' => array(
                array(
                    'icon'        => 'ion-ios7-monitor-outline',
                    'title'       => 'Galeri Peta Hidrometorologi',
                    'description' => 'Menyimpan dan menyajikan peta dan data spasial hidrometeorologi dalam bentuk GIS web services (WMS, WFS, WCS, KML)'
                ),
                array(
                    'icon'        => 'ion-waterdrop',
                    'title'       => 'Informasi Hidrometeorologi',
                    'description' => 'Informasi hidrometeorologi terkini dalam bentuk dokumen, artikel dan berita disajikan secara cepat dan akurat.'
                ),
                array(
                    'icon'        => 'ion-ios7-analytics-outline',
                    'title'       => 'Data Analisis',
                    'description' => 'Visualisasi spasial dapat dilakukan secara online dengan eksplorasi peta dan data spasial baik dari simpul SIH3 maupun dari sumber online lainnya.'
                ),
                array(
                    'icon'        => 'ion-android-share',
                    'title'       => 'Berbagi Data',
                    'description' => 'Pengguna terdaftar dapat membuat peta dan berbagi data spasial dengan pengguna lainnya.'
                ),
            )
        );

        $a = $this->_helper->Web->getArticles("", 2, 0);
        $artikel = array();
        foreach ($a["rows"] as $key=>$val) {
            $artikel[] = array(
                    "title" => $val["judul"],
                    "url" => "artikel/".$val["id"],
                    "content" => $this->_helper->Web->character_limiter($val["konten"])
                );
        }
        $this->view->artikel = $artikel;

        $d = $this->_helper->Web->getDocs("", 5, 0);
        $dokumen = array();
        foreach ($d["rows"] as $key=>$val) {
            $dokumen[] = array(
                    "title" => $val["judul"],
                    "url" => "dokumen/".$val["id"],
                    "content" => $this->_helper->Web->character_limiter($val["konten"])
                );
        }

        $this->view->dokumen = $dokumen;
    }

    public function artikelajaxAction()
    {
        $results = array(
            array(
                'kategori' => array(
                    'url' => '#kategori1',
                    'title' => 'Kategori 1'
                ),
                'item' => array(
                    'url' => '#item1',
                    'title' => 'Item 1',
                    'content' => 'Content 1'
                )
            ),
            array(
                'kategori' => array(
                    'url' => '#kategori2',
                    'title' => 'Kategori 2'
                ),
                'item' => array(
                    'url' => '#item2',
                    'title' => 'Item 2',
                    'content' => 'Content 2'
                )
            ),
            array(
                'kategori' => array(
                    'url' => '#kategori3',
                    'title' => 'Kategori 3'
                ),
                'item' => array(
                    'url' => '#item3',
                    'title' => 'Item 3',
                    'content' => 'Content 3'
                )
            )
        );
        echo json_encode($results);
        exit;
    }

    public function dokumenajaxAction()
    {
        $results = array(
            array(
                'kategori' => array(
                    'url' => '#kategori1',
                    'title' => 'Kategori 1'
                ),
                'item' => array(
                    'url' => '#item1',
                    'title' => 'Item 1',
                    'content' => 'Content 1'
                )
            ),
            array(
                'kategori' => array(
                    'url' => '#kategori2',
                    'title' => 'Kategori 2'
                ),
                'item' => array(
                    'url' => '#item2',
                    'title' => 'Item 2',
                    'content' => 'Content 2'
                )
            ),
            array(
                'kategori' => array(
                    'url' => '#kategori3',
                    'title' => 'Kategori 3'
                ),
                'item' => array(
                    'url' => '#item3',
                    'title' => 'Item 3',
                    'content' => 'Content 3'
                )
            )
        );
        echo json_encode($results);
        exit;
    }

    public function testAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        print_r($this->_helper->Web->getLastPeta());
    }
}
