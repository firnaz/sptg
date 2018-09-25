<?php
class ArtikelController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->page_id = "artikel";
        parent::init();
    }

    public function indexAction()
    {
        $page = $this->getRequest()->getParam("pages");
        $limit = 10;
        $start = ($page>0)?(($page-1)*$limit):0;
        $articles = $this->_helper->Web->getArticles("", $limit, $start);

        $categories_raw = $this->_helper->Web->getArticleCategories();
        $categories = array();
        if ($categories_raw) {
            foreach ($categories_raw as $category) {
                $categories[] = array(
                'title' => $category['kategori'],
                'url'   => $this->view->_URL . '/artikel/kategori/'.$category['id']
            );
            }
        }
        $this->view->categories = $categories;

        $this->view->page_title = 'Artikel';
        $pages = $articles['pages'];
        $pages['base'] = $this->view->_URL . '/artikel/' . $id ;
        $this->view->pages = $pages;

        $result_articles = array();
        if ($articles['rows']) {
            foreach ($articles['rows'] as $article) {
                $result_articles[] = array(
                'date'    => date('d F Y', strtotime($article['time_created'])),
                'title'   => $article['judul'],
                'content' => $article['konten'],
                'url'     => $this->view->_URL . '/artikel/detail/' . $article['id']
            );
            }
        }
        $this->view->list = $result_articles;

        $children = $this->_helper->Web->getChildren();
        if (count($children)>0) {
            $children_res = array();
            foreach ($children as $child) {
                $children_res[] = array(
                    'title'    => $child["nama_sistem"],
                    'ajaxurl'  => 'agr/'.$this->view->page_id.'/'.$child["id"],
                    'url'      => $this->view->page_id.'/simpul/'.$child["id"],
                    'selected' => false
                );
            }

            $conf = Zend_Registry::get('config');
            $this->view->source_switcher = array_merge(
                array(
                array(
                    'title'    => $conf["app_name"],
                    'url'      => $this->view->page_id,
                    'selected' => true
                )),
                $children_res
            );
        }
    }

    public function detailAction()
    {
        $id = $this->getRequest()->getParam("id");
        $article = $this->_helper->Web->getArticleById($id);
        $this->view->article = array(
            'date'    => date('d F Y', strtotime($article['time_created'])),
            'title'   => $article['judul'],
            'content' => $article['konten'],
        );

        $categories_raw = $this->_helper->Web->getArticleCategories();
        $categories = array();
        if ($categories_raw) {
            foreach ($categories_raw as $category) {
                $categories[] = array(
                'title' => $category['kategori'],
                'url'   => $this->view->_URL . '/artikel/kategori/'.$category['id']
            );
            }
        }
        $this->view->categories = $categories;
    }
    
    public function kategoriAction()
    {
        $id = $this->getRequest()->getParam("id");
        $page = $this->getRequest()->getParam("pages");
        $limit = 1;
        $start = ($page>0)?(($page-1)*$limit):0;
        $articles = $this->_helper->Web->getArticles("id_kategori='$id'", $limit, $start);

        $this->view->current = $this->_helper->Web->getArticleCategory($id);

        $result_articles = array();
        if ($articles['rows']) {
            foreach ($articles['rows'] as $article) {
                $result_articles[] = array(
                'date'    => date('d F Y', strtotime($article['time_created'])),
                'title'   => $article['judul'],
                'content' => $article['konten'],
                'url'     => $this->view->_URL . '/artikel/detail/' . $article['id']
            );
            }
        }
        $this->view->list = $result_articles;

        $pages = $articles['pages'];
        $pages['base'] = $this->view->_URL . '/artikel/kategori/' . $id ;
        $this->view->pages = $pages;

        $categories_raw = $this->_helper->Web->getArticleCategories();
        $categories = array();
        if ($categories_raw) {
            foreach ($categories_raw as $category) {
                $categories[] = array(
                'title' => $category['kategori'],
                'url'   => $this->view->_URL . '/artikel/kategori/'.$category['id']
            );
            }
        }
        $this->view->categories = $categories;
    }

    public function simpulAction()
    {
        $id_simpul = $this->getRequest()->getParam("id");
        $children = $this->_helper->Web->getChildren();
        if (count(children)) {
            $children_res = array();
            foreach ($children as $child) {
                if ($id_simpul==$child["id"]) {
                    $children_active = array(
                        'title'    => $child["nama_sistem"],
                        'ajaxurl'  => 'agr/artikel/'.$child["id"],
                        'url'      => 'artikel/simpul/'.$child["id"],
                        'selected' => ($id_simpul==$child["id"])?true:false
                    );
                    $children_res[] = $children_active;
                } else {
                    $children_res[] = array(
                        'title'    => $child["nama_sistem"],
                        'ajaxurl'  => 'agr/'.$this->view->page_id.'/'.$child["id"],
                        'url'      => $this->view->page_id.'/simpul/'.$child["id"],
                        'selected' => false
                    );
                }
            }

            $conf = Zend_Registry::get('config');
            $this->view->source_switcher = array_merge(
                array(
                array(
                    'title'    => $conf["app_name"],
                    'url'      => $this->view->page_id,
                    'selected' => false
                )),
                $children_res
            );
        } else {
            $this->_redirect($this->view->page_id);
        }
        $this->view->children_active = $children_active;
        // yang aktif $children_active
    }
}
