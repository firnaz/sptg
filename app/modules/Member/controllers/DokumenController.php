<?php
class Member_DokumenController extends Member_Controller_Action
{
    public function init(){
        $this->view->page_id = "dokumen";
        parent::init();
    }
    public function indexAction()
    {
        // filter
        $this->view->filter_author = ($_GET['filter_author']) ? $_GET['filter_author'] : 'all';

        $limit = 5;
        $page = $this->getRequest()->getParam("pages");
        $start = ($page>0)?(($page-1)*$limit):0;
        $where = '1=1';
        if($this->view->filter_author=="me"){
            $where .= " AND (user_creator='".$this->member_auth->username."' OR member_creator='".$this->member_auth->username."')";
        }

        // documents
        $d = $this->_helper->Web->getDocs($where,$limit,$start);
        $dokumen = array();
        foreach($d["rows"] as $key=>$val){
            $dokumen[] = array(
                    "title" => $val["judul"],
                    'url'  => $this->view->_URL . '/member/dokumen/detail/' . $val['id'],
                    'mode' =>  ($val['member_creator']==$this->member_auth->username || $val['user_creator']==$this->member_auth->username)? 'edit' : 'view'
                );
        }
        $this->view->dokumen = $dokumen;
        $this->view->pages = $d['pages'];
	}
    public function uploadAction()
    {
        $params = $this->getRequest()->getParams();
        $message = "";
        // print_r(($_FILES["file"]["size"][0]));exit;
        if($params["submit"]){
            if (trim($params["judul"]) && $params["category"] && $_FILES["file"]["size"][0]>0){
                $title = $params["judul"];
                $abstract = $params["abstract"];
                $kategori = $params["category"];
                // $peta = $params["map"];
                $action = $this->_helper->Members->addMemberDokumen($title, $abstract, $kategori, $peta);
                if (!$action["success"]){
                    $message.=$action["reason"];
                }else{
                    $message.="Dokumen berhasil ditambahkan.";
                    $this->_redirect('/member/dokumen');
                }
            }else{
                if(!trim($params["judul"])){
                    $message .= "Judul harus diisi dengan benar.<br/>";
                }
                if(!$params["category"]){
                    $message .= "Anda harus memilih Kategori.<br/>";
                }
                if($_FILES['file']["size"][0]>0){
                    $message .= "Anda belum mengisi file..<br/>";
                }
            }
        }

        $c = $this->_helper->Web->getDocumentCategories();
        $categories_res = array();
        foreach($c as $category){
            $categories_res[] = array("value"=>$category["id"], "name"=>$category["kategori"]);
        }
        $this->view->categories = $categories_res;
    }
    public function detailAction(){
        $this->view->filter_author = ($_GET['filter_author']) ? $_GET['filter_author'] : 'all';

        $limit = 5;
        $page = $this->getRequest()->getParam("pages");
        $start = ($page>0)?(($page-1)*$limit):0;
        $where = '1=1';
        if($this->view->filter_author=="me"){
            $where .= " AND (user_creator='".$this->member_auth->username."' OR member_creator='".$this->member_auth->username."')";
        }

        // documents
        $d = $this->_helper->Web->getDocs($where,$limit,$start);
        $dokumen = array();
        foreach($d["rows"] as $key=>$val){
            $dokumen[] = array(
                    "title" => $val["judul"],
                    "url" => $this->view->_URL . "/member/dokumen/detail/".$val["id"]
                );
        }
        $this->view->dokumen = $dokumen;


        $id_dokumen = $this->getRequest()->getParam("id");
        $doc = $this->_helper->Web->getDocsById($id_dokumen);
        $doc["mode"] = ($doc['member_creator']==$this->member_auth->username || $doc['user_creator']==$this->member_auth->username)? 'edit' : 'view';
        $this->view->document = $doc;
        // print_r($this->view->document);
        // exit;
    }
    public function editAction(){
        $id_dokumen = $this->getRequest()->getParam("id");
        $doc = $this->_helper->Web->getDocsById($id_dokumen);
        if($_POST){
            if($doc["user_creator"]!=$this->member_auth->username && $doc["member_creator"]!=$this->member_auth->username){
                $this->_redirect('/member/dokumen/detail/'.$id_dokumen);
                exit;
            }

            $params = $this->getRequest()->getParams();
            if (trim($params["judul"]) && $params["category"]){
                $title = $params["judul"];
                $abstract = $params["abstract"];
                $kategori = $params["category"];
                // $peta = $params["map"];
                $action = $this->_helper->Members->updateMemberDokumen($id_dokumen, $title, $abstract, $kategori, $peta);
                // if ($action["success"]){
                //     $message.="Dokumen berhasil ditambahkan.";
                // }
                $this->_redirect('/member/dokumen/detail/'.$id_dokumen);
            }else{
                if(!trim($params["judul"])){
                    $message .= "Judul harus diisi dengan benar.<br/>";
                }
                if(!$params["category"]){
                    $message .= "Anda harus memilih Kategori.<br/>";
                }
            }
        }
        $c = $this->_helper->Web->getDocumentCategories();
        $categories_res = array();
        foreach($c as $category){
            $categories_res[] = array("value"=>$category["id"], "name"=>$category["kategori"]);
        }
        $this->view->categories = $categories_res;

        $this->view->document = $doc;
        $this->view->id_dokumen = $id_dokumen;
    }
    public function deleteAction(){
        $id_dokumen = $this->getRequest()->getParam("id");
        $result = $this->_helper->Members->deleteMemberDokumen($id_dokumen);
        $redirect = $this->view->_URL . '/member/dokumen';
        $this->_redirect($redirect);
        exit;      
    }

    public function fileuploadAction(){
        $id_dokumen = $this->getRequest()->getParam('id');
        $doc = $this->_helper->Web->getDocsById($id_dokumen);
        if($doc["user_creator"]!=$this->member_auth->username && $doc["member_creator"]!=$this->member_auth->username){
            $this->_redirect('/member/dokumen/detail/'.$id_dokumen);
            exit;
        }

        $result = $this->_helper->Members->addMemberDokumenFile($id_dokumen);
        $this->_redirect('member/dokumen/detail/'.$id_dokumen);
    }

    public function filedeleteAction(){
        $id_dokumen = $this->getRequest()->getParam('id');
        $id_dokumen_file = $this->getRequest()->getParam('id_file');
        $doc = $this->_helper->Web->getDocsById($id_dokumen);
        if($doc["user_creator"]!=$this->member_auth->username && $doc["member_creator"]!=$this->member_auth->username){
            $this->_redirect('/member/dokumen/detail/'.$id_dokumen);
            exit;
        }
        $this->_helper->Members->deleteMemberDokumenFile($id_dokumen_file);
        $this->_redirect('member/dokumen/detail/'.$id_dokumen);
    }
}
?>