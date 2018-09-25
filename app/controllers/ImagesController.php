<?php
class ImagesController extends Zend_Controller_Action
{
    public function indexAction()
    {
	}
	public function layerthumbnailAction(){
		$this->_helper->viewRenderer->setNoRender();
		$file_image = $this->getRequest()->getParam("image");
		$width = $this->getRequest()->getParam("width");
		$height = $this->getRequest()->getParam("height");
		$fullpath = LAYER_THUMBNAIL."/".$file_image;
        if(file_exists($fullpath)){
            $this->_helper->Sipitung->croppedresizeshow($fullpath,$width,$height);
        }
	}
	public function petathumbnailAction(){
		$this->_helper->viewRenderer->setNoRender();
		$file_image = $this->getRequest()->getParam("image");
		$width = $this->getRequest()->getParam("width");
		$height = $this->getRequest()->getParam("height");
		$fullpath = PETA_THUMBNAIL."/".$file_image;
        if(file_exists($fullpath)){
            $this->_helper->Sipitung->croppedresizeshow($fullpath,$width,$height);
        }else{
        	$id_peta = substr($file_image, 5,(strlen($file_image)-9));
        	// echo $id_peta;exit;
			$petagallery = new Petagallery();
			$row = $petagallery->fetchRow("id=$id_peta");
			$this->_helper->Sipitung->createPetaThumbnail($row->id,$row->x_min,$row->y_min,$row->x_max,$row->y_max);
	        if(file_exists($fullpath)){
	            $this->_helper->Sipitung->croppedresizeshow($fullpath,$width,$height);
	        }
        }
	}
	public function fotomemberAction(){
		$this->_helper->viewRenderer->setNoRender();
		$file_image = $this->getRequest()->getParam("image");
		$width = $this->getRequest()->getParam("width");
		$height = $this->getRequest()->getParam("height");
		$fullpath = FOTO_MEMBER."/".$file_image;
        if(file_exists($fullpath)){
            $this->_helper->Sipitung->croppedresizeshow($fullpath,$width,$height);
        }
	}
	public function artikelimagesAction(){
		$this->_helper->viewRenderer->setNoRender();
		$artikelimages = new Artikelimages();
		$id = $this->getRequest()->getParam("id");
		$file_image = $this->getRequest()->getParam("image");
		$width = $this->getRequest()->getParam("width");
		$height = $this->getRequest()->getParam("height");
		$dataartikelimages = $artikelimages->fetchRow("id='$id' AND nama_file='$file_image'");
		$fullpath = ARTIKEL_IMAGES."/".$dataartikelimages->nama_file_renamed;
        if(file_exists($fullpath)){
            $this->_helper->Sipitung->paddedresizeshow($fullpath,$width,$height);
        }
	}
	public function pagesimageAction(){
		$this->_helper->viewRenderer->setNoRender();
		$pages = new Pages();
		$id = $this->getRequest()->getParam("id");
		$file_image = $this->getRequest()->getParam("image");
		$width = $this->getRequest()->getParam("width");
		$height = $this->getRequest()->getParam("height");
		$datapages = $pages->fetchRow("id='$id' AND nama_file_gambar='$file_image'");
		$fullpath = GAMBAR_PAGES."/".$datapages->nama_file_gambar_renamed;
        if(file_exists($fullpath)){
            $this->_helper->Sipitung->paddedresizeshow($fullpath,$width,$height);
        }
	}
	public function previewAction(){
        $params = $this->getRequest()->getParams();
        $extent = json_decode($params["extent"]);
        $layers = json_decode($params["layers"]);
        $width = $params["width"];
        $height = $params["height"];

        $thumbnail = $this->_helper->Web->petaThumbnail($layers, $extent->minx, $extent->miny, $extent->maxx, $extent->maxy, $width, $height);
        $thumbnail->thumbnailImage($width,$height,true,true);
		$thumbnail->setImageFormat('PNG');
        header("Content-type: image/png");
        echo $thumbnail->getImageBlob();
        exit;
	}
}