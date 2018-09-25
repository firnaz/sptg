<?php
class Agr_IndexController extends Agr_Controller_Action
{
    public function indexAction()
    {
    	echo "Agregator SIH 3 API V 1.0";
	}
	public function dokumenAction(){
		$id_simpul = $this->getRequest()->getParam("id");
		$children = new Children();
		$data = array();
		$i = 0;
		if($id_simpul){
			$datachild = $children->fetchRow("id='".$id_simpul."'");
			$result = $this->query($datachild->url_api."/dokumen/view",10,$this->childCookies[$id_simpul]);
			if($result){
				foreach($result->rows as $row){
					$data[$i]= $row;
					$data[$i]->kategori= $datachild->nama_sistem;
					$data[$i]->href= $datachild->url_publik."/dokumen/detail/".$data[$i]->id;
					$data[$i]->content = $this->_helper->Web->character_limiter($data[$i]->konten);
					$data[$i]->url = $datachild->url_publik;
					$i++;
				}
			}
		}else{
			foreach($this->childCookies as $key=>$cookie){
				$datachild = $children->fetchRow("id='".$key."'");
				// print_r($datachild);exit;
				$result = $this->query($datachild->url_api."/dokumen/view",1,$cookie);
				if($result){
					foreach($result->rows as $row){
						$data[$i]= $row;
						$data[$i]->kategori= $datachild->nama_sistem;
						$data[$i]->href= $datachild->url_publik."/dokumen/detail/".$data[$i]->id;
						$data[$i]->content = $this->_helper->Web->character_limiter($data[$i]->konten);
						$data[$i]->url = $datachild->url_publik;
						$i++;
					}
				}
			}
		}
		echo json_encode($data);
	}
	public function artikelAction(){
		$id_simpul = $this->getRequest()->getParam("id");
		$children = new Children();
		$data = array();
		$i = 0;
		if($id_simpul){
			$datachild = $children->fetchRow("id='".$id_simpul."'");
			$result = $this->query($datachild->url_api."/artikel/view",10,$this->childCookies[$id_simpul]);
			if($result){
				foreach($result->rows as $row){
					$data[$i]= $row;
					$data[$i]->kategori= $datachild->nama_sistem;
					$data[$i]->href= $datachild->url_publik."/artikel/detail/".$data[$i]->id;
					$data[$i]->content = $this->_helper->Web->character_limiter($data[$i]->konten);
					$data[$i]->url = $datachild->url_publik;
					unset($data[$i]->konten);
					$i++;
				}
			}
		}else{
			foreach($this->childCookies as $key=>$cookie){
				$datachild = $children->fetchRow("id='".$key."'");
				// print_r($datachild);exit;
				$result = $this->query($datachild->url_api."/artikel/view",1,$cookie);
				if($result){
					foreach($result->rows as $row){
						$data[$i]= $row;
						$data[$i]->kategori= $datachild->nama_sistem;
						$data[$i]->href= $datachild->url_publik."/artikel/detail/".$data[$i]->id;
						$data[$i]->content = $this->_helper->Web->character_limiter($data[$i]->konten);
						$data[$i]->url = $datachild->url_publik;
						unset($data[$i]->konten);
						$i++;
					}
				}
			}			
		}
		echo json_encode($data);
	}
	public function petaAction(){
		$id_simpul = $this->getRequest()->getParam("id");
		$children = new Children();
		$data = array();
		$i = 0;
		if($id_simpul){
			$datachild = $children->fetchRow("id='".$id_simpul."'");
			$result = $this->query($datachild->url_api."/peta/view",12,$this->childCookies[$id_simpul]);
			if($result){
				foreach($result->rows as $row){
					$data[$i]= $row;
					$data[$i]->nama_sistem= $datachild->nama_sistem;
					$data[$i]->url= $datachild->url_publik."/peta/detail/".$data[$i]->url_segment;
					$data[$i]->thumbnail = $datachild->url_publik . '/images/peta/thumbnail/200/200/peta-'.$data[$i]->id.'.png';
					$i++;
				}
			}			
		}else{
			foreach($this->childCookies as $key=>$cookie){
				$datachild = $children->fetchRow("id='".$key."'");
				// print_r($datachild);exit;
				$result = $this->query($datachild->url_api."/peta/view",1,$cookie);
				if($result){
					foreach($result->rows as $row){
						$data[$i]= $row;
						$data[$i]->nama_sistem= $datachild->nama_sistem;
						$data[$i]->url= $datachild->url_publik."/peta/detail/".$data[$i]->url_segment;
						$data[$i]->thumbnail = $datachild->url_publik . '/images/peta/thumbnail/200/200/peta-'.$data[$i]->id.'.png';
						$i++;
					}
				}
			}
		}
		echo json_encode($data);
	}
}
?>