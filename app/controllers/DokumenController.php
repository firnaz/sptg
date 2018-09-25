<?php
class DokumenController extends Zend_Controller_Action
{
    public function init(){
        $this->view->page_id = "dokumen";
        parent::init();
    }
    public function indexAction()
    {

        $page = $this->getRequest()->getParam("pages");
        $limit = 10;
        $start = ($page>0)?(($page-1)*$limit):0;
		$documents = $this->_helper->Web->getDocs("",$limit,$start);

		$categories_raw = $this->_helper->Web->getDocumentCategories();
        $categories = array();
        if($categories_raw)
        foreach($categories_raw as $category){
            $categories[] = array(
                'title' => $category['kategori'],
                'url'   => $this->view->_URL . '/dokumen/kategori/'.$category['id']
            );
        }
        $this->view->categories = $categories;

        $this->view->page_title = 'Dokumen';
        $pages = $documents['pages'];
        $pages['base'] = $this->view->_URL . '/dokumen';
        $this->view->pages = $pages;

        $result_documents = array();
        if($documents['rows'])
        foreach($documents['rows'] as $document){
            $result_documents[] = array(
                'date'    => date('d F Y',strtotime($document['time_created'])),
                'title'   => $document['judul'],
                'content' => $document['konten'],
                'url'     => $this->view->_URL . '/dokumen/detail/' . $document['id']
            );
        }
        $this->view->list = $result_documents;

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
		$id_dokumen = $this->getRequest()->getParam("id");
		$this->view->document = $this->_helper->Web->getDocsById($id_dokumen);
	}

    public function kategoriAction(){
        $id_kategori = $this->getRequest()->getParam("id");
        $page = $this->getRequest()->getParam("pages");
        $limit = 10;
        $start = ($page>0)?(($page-1)*$limit):0;
		$documents = $this->_helper->Web->getDocs("id_kategori='$id_kategori'",$limit, $start);

        $this->view->current = $this->_helper->Web->getDocumentCategoryById($id_kategori);

        $result_documents = array();
        if($documents['rows'])
        foreach($documents['rows'] as $document){
            $result_documents[] = array(
                'date'    => date('d F Y',strtotime($document['time_created'])),
                'title'   => $document['judul'],
                'content' => $document['konten'],
                'url'     => $this->view->_URL . '/dokumen/detail/' . $document['id']
            );
        }
        $this->view->list = $result_documents;

        $pages = $document['pages'];
        $pages['base'] = $this->view->_URL . '/dokumen/kategori/' . $id ;
        $this->view->pages = $pages;

        $categories_raw = $this->_helper->Web->getDocumentCategories();
        $categories = array();
        if($categories_raw)
        foreach($categories_raw as $category){
            $categories[] = array(
                'title' => $category['kategori'],
                'url'   => $this->view->_URL . '/dokumen/kategori/'.$category['id']
            );
        }
        $this->view->categories = $categories;
	}

	public function downloadAction(){
		$this->_helper->viewRenderer->setNoRender();
		$nama_file = $this->getRequest()->getParam("file");
		$id = $this->getRequest()->getParam("id");
		$dokumenfile = new Dokumenfile();
		$dok = $dokumenfile->fetchRow("id_dokumen='$id' AND nama_file='$nama_file'");
		// print_r($dok->nama_file_renamed);
		$file = DOKUMEN_FILE."/".$dok->nama_file_renamed;
		if(file_exists($file)){
			$content = file_get_contents($file);
	        $mime = mime_content_type($file);
			header("last-modified: " .gmstrftime("%a, %d %b %Y %T %Z", filemtime($file)));
	        header("Content-type: $mime");
	        echo $content;
	        exit;
		}
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
                    'url'      => $this->view->page_id,
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