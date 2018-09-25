<?php 
class Api_StoreController extends Api_Controller_Action
{
    public function indexAction()
    {

    }
	public function kategoriartikelAction(){
		$kategoriartikel = new Kategoriartikel();
		$data = $kategoriartikel->fetchAll($kategoriartikel->select()->order("kategori ASC"));
		echo  "{rows:".Zend_Json::encode($data->toArray())."}";
	}
	public function kategorilayerAction(){
		$kategorilayer = new Kategorilayer();
		$data = $kategorilayer->fetchAll($kategorilayer->select()->order("nama_kategori ASC"));
		echo  "{rows:".Zend_Json::encode($data->toArray())."}";
	}
	public function kategoridokumenAction(){
		$kategoridokumen = new Kategoridokumen();
		$data = $kategoridokumen->fetchAll($kategoridokumen->select()->order("kategori ASC"));
		echo  "{rows:".Zend_Json::encode($data->toArray())."}";
	}
	public function petagalleryAction(){
		$petagallery = new Petagallery();
		$data = $petagallery->fetchAll($petagallery->select()->order("judul ASC"));
		echo  "{rows:".Zend_Json::encode($data->toArray())."}";
	}
}
?>