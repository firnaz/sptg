<?php
class Member_ProfilController extends Member_Controller_Action
{
    public function init(){
        $this->view->page_id = "profil";
        parent::init();
    }
    public function indexAction()
    {
        $params = $this->getRequest()->getParams();
        // print_r($params);exit;

        if($params["submit"]){
            $member = new Member();
            if(trim($password)){
                $data['password']           ="plain:".md5($password);
            }
            $data['nama_lengkap']       =$params["nama"];
            $data['instansi']           =$params["instansi"];
            $data['alamat_instansi']    =$params["alamat"];
            $data['jenis_kelamin']      =$params["jenis_kelamin"];
            $where = $member->getAdapter()->quoteInto('name= ?', $this->member_auth->username);
            $member->update($data, $where);
            $this->member_auth->nama = $params["nama"];
            $this->member_auth->instansi = $params["instansi"];
            $this->member_auth->alamat_instansi = $params["alamat"];
            $this->member_auth->jenis_kelamin = $params["jenis_kelamin"];
        }
        $this->view->member = array(
            'username' => $this->member_auth->username,
            'name' => $this->member_auth->nama,
            'email' => 'admin@agrisoft-cb.com',
            'nama_instansi' => $this->member_auth->instansi,
            'alamat_instansi' => $this->member_auth->alamat_instansi,
            'jenis_kelamin' => $this->member_auth->jenis_kelamin
        );

        // hz: nas, ini gw ngambil dashboard, harusnya query buat ambil layer sendiri
        $layers = $this->_helper->Web->getLayerList("contributor='".$this->member_auth->username."'", 3, 0);
        $layers_res = array();
        if($layers['rows'])
        foreach($layers['rows'] as $layer){
            $layers_res[] = array(
                'title'      => $layer['title'],
                'author'     => $layer['contributor'],
                'star'       => $layer['rating'],
                'view_count' => $layer['num_view'],
                'url'        => $this->view->_URL . '/member/layer/detail/' . $layer['identifier']
            );
        }
        $this->view->layers = $layers_res;

        // hz: nas, ini gw ngambil dashboard, harusnya query buat ambil peta sendiri
        $maps = $this->_helper->Web->getPeta("user_creator='".$this->member_auth->username."'", 3, 0);
        $maps_res = array();
        if($maps['rows'])
        foreach($maps['rows'] as $map){
            $maps_res[] = array(
                'title'      => $map['judul'],
                'author'     => $map['user_creator'],
                'star'       => $map['rating'],
                'view_count' => $map['num_view'],
                'url'        => $this->view->_URL . '/member/peta/detail/' . $map['url_segment']
            );
        }
        $this->view->maps = $maps_res;
	}
}
?>