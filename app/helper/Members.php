<?php
class Members extends Zend_Controller_Action_Helper_Abstract {
	private $geoserver;
	private $pycsw;
	private $sipitung;
	private $web;
	private $db;
	private $member;
	private $config;
	private $fields=array(
				"title"       => "dc:title",
				"modified"    => "dct:modified",
				"abstract"    => "dct:abstract",
				"type"        => "dc:type",
				"subject"     => "dc:subject",
				"format"      => "dc:format",
				"publisher"   => "dc:publisher",
				"creator"     => "dc:creator",
				"language"    => "dc:language",
				"contributor" => "dc:contributor"
			);

	public function __construct(){
		$this->geoserver = Zend_Controller_Action_HelperBroker::getStaticHelper('Geoserver');
		$this->sipitung = Zend_Controller_Action_HelperBroker::getStaticHelper('Sipitung');
		$this->pycsw = Zend_Controller_Action_HelperBroker::getStaticHelper('PyCswClient');
		$this->web = Zend_Controller_Action_HelperBroker::getStaticHelper('Web');
		$this->db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$this->member = new Member();
		$this->config = Zend_Registry::get('config');
	}
	function checkMemberLogin(){
		$member_auth = new Zend_Session_Namespace('member_auth');
		if ($member_auth->username){
			$result['time'] = $member_auth->time; 
			$result['username']= $member_auth->username;
			$result['success'] = "true";
		}else{
			$result= false; 
		}		
		return $result;
	}
	function getMemberDetail($name){
		$row = $this->member->fetchRow($this->member->select()->from($member, array('username'=>'name','enabled','nama_lengkap','instansi','alamat_instansi','jenis_kelamin','foto_filename','foto_file_renamed','num_layer_uploaded','num_peta_created'))->where("name='$name'"));
		return $row?$row->toArray():false;
	}
	function getMemberPassword($name){
		$row = $this->member->fetchRow("name='$name'");
		if($row){
			return str_replace("plain:","",$row->password);
		}else{
			return false;
		}
	}
	function login($username, $password){
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();

		$uname = $username;
		$passwd = "plain:".md5($password);

		$authAdapter = new Zend_Auth_Adapter_DbTable($db,'users','name','password', "? AND ENABLED='Y'");
		$authAdapter->setIdentity($uname)->setCredential($passwd);

		$auth = Zend_Auth::getInstance();
		$authenticate = $auth->authenticate($authAdapter);
		$data = $authAdapter->getResultRowObject(null,'password');
		$auth->getStorage()->write($data);
		
		if(!$authenticate->isValid()){
			$auth  = Zend_Auth::getInstance();
		  	$auth->clearIdentity();
			Zend_Session::namespaceUnset("member_auth");
			$result["success"] = false;
			$result["reason"] = "Username dan Password Salah!!!";
		}else{
			$member_auth = new Zend_Session_Namespace('member_auth');
			$member_auth->username=$auth->getIdentity()->name;
			$member_auth->nama=$auth->getIdentity()->nama_lengkap;
			$member_auth->instansi=$auth->getIdentity()->instansi;
			$member_auth->alamat_instansi=$auth->getIdentity()->alamat_instansi;
			$member_auth->foto=$auth->getIdentity()->foto_filename;
			$member_auth->jenis_kelamin = $auth->getIdentity()->jenis_kelamin;
			$time = date("Y-m-d G:i:s");
			$member_auth->time= $time;
			$result["success"] = true;
		}
		return $result;		
	}
	public function logout(){
		$auth  = Zend_Auth::getInstance();
	  	$auth->clearIdentity();
		Zend_Session::namespaceUnset("member_auth");
		$result["success"] = "true";
		echo $result;
	}
	public function getMemberLayers($limit, $start){
		$member_auth = new Zend_Session_Namespace('member_auth');
		// print_r($member_auth->username);
		$filter ="<ogc:Filter>
					<ogc:PropertyIsEqualTo>
					<ogc:PropertyName>dc:contributor</ogc:PropertyName>
					<ogc:Literal>".$member_auth->username."</ogc:Literal>
					</ogc:PropertyIsEqualTo>
				</ogc:Filter>";
		return $this->web->getLayerList($filter, $limit, $start);
	}
	public function getMemberPeta($limit, $start){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$where = "user_creator='".$member_auth->username."'";
		return $this->web->getPeta($where,$limit,$start);
	}
	public function getMemberDocs($limit, $start){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$where = "member_creator='".$member_auth->username."'";
		return $this->web->getPeta($where,$limit,$start);
	}

	public function addMemberLayers($title, $abstract, $subject, $tipe_layer){
		// echo $tipe_layer;exit;
		$member_auth = new Zend_Session_Namespace('member_auth');
		$dir= TMP_DIR."/shp";
		$upload = new Zend_File_Transfer_Adapter_Http();

		$upload->setDestination($dir)->addValidator('Extension', false, 'zip');
		$files  = $upload->getFileInfo();
		$uploaded = false;
		foreach($files as $file=>$info){
			if ($upload->isUploaded($file)) {
				if ($upload->isValid($file)) {
					if($upload->receive($file)){
						$extension = pathinfo($info['name'], PATHINFO_EXTENSION); 
						$layer_file = $upload->getFileName($file);
						$uploaded = true;
						//echo $nama_file;exit;
						//$zip_file = $dir."/".$nama_file;
					}else{
						$reason = "Upload Gagal.";						
					}
				}else{
					$reason = "Tipe file tidak benar.";
				}
			}
		}
		if($uploaded){
			$password = $this->getMemberPassword($member_auth->username);
			if($tipe_layer=="vektor"){
				$addLayer= $this->geoserver->addFeature($title, $abstract, $layer_file, $member_auth->username, $password);
			}else{
				$addLayer= $this->geoserver->addCoverage($title, $abstract, $layer_file, $member_auth->username, $password);
			}
			@unlink($layer_file);
			if($addLayer["success"]){
				// $subj = explode(",",$subject);
				$xmlSubject = "";
				foreach ($subject as $key=>$val){
					$xmlSubject .= '<dc:subject>'.$val.'</dc:subject>';
				}
				$identifier = uniqid();
				$xml = '<csw:Record xmlns="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/record.xsd">
					    <dc:identifier>'.$identifier.'</dc:identifier>
					    <dc:title>'.$title.'</dc:title>
					    <dct:modified>'.date('Y-m-d').'</dct:modified>
					    <dct:abstract>'.$abstract.'</dct:abstract>
					    <dc:type>dataset</dc:type><dc:type>service</dc:type>'.$xmlSubject.'
					    <dc:format>'.$this->config["default_layer_format"].'</dc:format>
					    <dc:creator>'.$member_auth->instansi.'</dc:creator>
					    <dc:publisher>'.$member_auth->instansi.'</dc:publisher>
					    <dc:contributor>'.$member_auth->username.'</dc:contributor>
					    <dc:language>id</dc:language>
					    <ows:BoundingBox crs="EPSG:'.$addLayer["epsg"].'">
					      <ows:LowerCorner>'.$addLayer['extent']['xmin'].' '.$addLayer['extent']['ymin'].'</ows:LowerCorner>
					      <ows:UpperCorner>'.$addLayer['extent']['xmax'].' '.$addLayer['extent']['ymax'].'</ows:UpperCorner>
					    </ows:BoundingBox>
					  </csw:Record>';
				$response= $this->sipitung->xml2array($this->pycsw->insert($xml));
				if($response["csw:TransactionResponse"]["csw:TransactionSummary"]["csw:totalInserted"]){
					$cswlayer = new Cswlayer();
					$row = $cswlayer->createRow();
					$row->identifier = $identifier;
					$row->layer = $addLayer["name"];
					$row->tipe_layer = $tipe_layer;
					$row->save();

					$identifier = $response["csw:TransactionResponse"]["csw:InsertResult"]["csw:BriefRecord"]["dc:identifier"];
					$result["success"] = true;
				}else{
					$result["success"] = false;
					$result["reason"] = "Gagal menambah Layer!";
				}
			}else{
				$result["success"] = false;
				$result["reason"] = $addLayer["reason"];
			}			
		}else{
			$result["success"] = false;
			$result["reason"] = $reason;			
		}	
		return $result;	
	}
	public function updateMemberLayer($identifier, $title, $abstract, $subject){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$data= $this->pycsw->getRecordsById($identifier);

		$cswlayer = new Cswlayer();
		$datacswlayer = $cswlayer->fetchRow("identifier='$identifier'");

		if($datacswlayer->tipe_layer=='vektor'){
			$this->geoserver->updateFeature($title, $abstract, $datacswlayer->layer, $member_auth->username, $this->getMemberPassword($member_auth->username));
		}else{
			$this->geoserver->updateCoverage($title, $abstract, $datacswlayer->layer, $member_auth->username, $this->getMemberPassword($member_auth->username));
		}

		$xmlSubject = "";
		foreach ($subject as $key=>$val){
			$xmlSubject .= '<dc:subject>'.$val.'</dc:subject>';
		}
		$xml = '<csw:Record xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/record.xsd" xmlns="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
			    <dc:identifier>'.$data['identifier'].'</dc:identifier>
			    <dc:title>'.$title.'</dc:title>
			    <dct:modified>'.date('Y-m-d').'</dct:modified>
			    <dct:abstract>'.$abstract.'</dct:abstract>
			    <dc:type>dataset</dc:type><dc:type>service</dc:type>'.$xmlSubject.'
			    <dc:format>'.$this->config["default_layer_format"].'</dc:format>
			    <dc:creator>'.$member_auth->instansi.'</dc:creator>
			    <dc:publisher>'.$member_auth->instansi.'</dc:publisher>
			    <dc:contributor>'.$member_auth->username.'</dc:contributor>
			    <dc:language>id</dc:language>
			    <ows:BoundingBox crs="'.$data["srs"].'">
			      <ows:LowerCorner>'.$data['xmin'].' '.$data['ymin'].'</ows:LowerCorner>
			      <ows:UpperCorner>'.$data['xmax'].' '.$data['ymax'].'</ows:UpperCorner>
			    </ows:BoundingBox>
			  </csw:Record>';
		$response= $this->sipitung->xml2array($this->pycsw->update($xml));

		if($response["csw:TransactionResponse"]["csw:TransactionSummary"]["csw:totalUpdated"]){
			$identifier = $response["csw:TransactionResponse"]["csw:InsertResult"]["csw:BriefRecord"]["dc:identifier"];
			$result["success"] = true;
		}else{
			$result["success"] = false;
			$result["reason"] = "Gagal mengubah metadata!";
		}
		return $result;
	}
	public function deleteMemberLayer($identifier){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$data= $this->pycsw->getRecordsById($identifier);
		$cswlayer = new Cswlayer();
		$datacswlayer = $cswlayer->fetchRow("identifier='$identifier'");

		//delete layer from geoserver
		if($datacswlayer->tipe_layer=='vektor'){
			$this->geoserver->deleteFeature($datacswlayer->layer, $member_auth->username, $this->getMemberPassword($member_auth->username));
		}else{
			$this->geoserver->deleteCoverage($datacswlayer->layer, $member_auth->username, $this->getMemberPassword($member_auth->username));
		}
		$this->geoserver->deleteStyle($datacswlayer->layer."_style", $member_auth->username, $this->getMemberPassword($member_auth->username));
		$xml = '<ogc:Filter>
					<ogc:PropertyIsEqualTo>
						<ogc:PropertyName>apiso:Identifier</ogc:PropertyName>
						<ogc:Literal>'.$identifier.'</ogc:Literal>
					</ogc:PropertyIsEqualTo>
				</ogc:Filter>';
		$response= $this->sipitung->xml2array($this->pycsw->delete($xml));
		if($response["csw:TransactionResponse"]["csw:TransactionSummary"]["csw:totalDeleted"]){
			$cswlayer = new Cswlayer();
			$where = $cswlayer->getAdapter()->quoteInto('identifier= ?', $identifier);
			$cswlayer->delete($where);
			$result["success"] = true;
		}else{
			$result["success"] = false;
			$result["reason"] = "Gagal menghapus layer!";
		}
		return $result;
	}
	public function addMemberPeta($judul, $deskripsi, $x_min, $y_min, $x_max, $y_max, $layer){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$petagallery = new Petagallery();

		$url_segment = $this->sipitung->generateURLSegment($judul);
		$checkdoc = $petagallery->fetchRow("url_segment='$url_segment'");
		$i=2;
		while(count($checkdoc)>0){
			$url_segment = $url_segment.$i;
			$checkdoc = $petagallery->fetchRow("url_segment='$url_segment'");
			$i++;
		}
		
		$row  = $petagallery->createRow();
		$row->judul        = $judul;
		$row->deskripsi    = $deskripsi;
		$row->time_created = date("Y-m-d G:i:s");
		$row->user_creator = $member_auth->username;
		$row->is_approved  = 1;
		$row->x_min        = $x_min;
		$row->y_min        = $y_min;
		$row->x_max        = $x_max;
		$row->y_max        = $y_max;
		$row->url_segment  = $url_segment;
		$id  = $row->save();

		$this->addPetaLayer($id,$layer);
		$this->savePetaThumbnail($id, $layer, $x_min, $y_min, $x_max, $y_max);
	}

	public function updateMemberPeta($id, $judul, $deskripsi, $x_min, $y_min, $x_max, $y_max, $layer){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$petagallery = new Petagallery();

		$url_segment = $this->sipitung->generateURLSegment($judul);
		$checkdoc = $petagallery->fetchRow("url_segment='$url_segment' and id!='$id'");
		$i=2;
		while(count($checkdoc)>0){
			$url_segment = $url_segment.$i;
			$checkdoc = $petagallery->fetchRow("url_segment='$url_segment' and id!='$id'");
			$i++;
		}

		$data['judul']        = $judul;
		$data['deskripsi']    = $deskripsi;
		$data['is_approved']  = 1;
		$data['url_segment']  = $url_segment;
		$data['x_min']        = $x_min;
		$data['y_min']        = $y_min;
		$data['x_max']        = $x_max;
		$data['y_max']        = $y_max;
		$data['user_creator'] = $member_auth->username;
		
		$where = $petagallery->getAdapter()->quoteInto('id= ?', $id);
		$petagallery->update($data, $where);

		$this->addPetaLayer($id,$layer);
		$this->savePetaThumbnail($id, $layer,$x_min, $y_min, $x_max, $y_max);
	}

	public function deleteMemberPeta($id)
	{		
		$petagallery = new Petagallery();
		$petalayer= new Petalayer();

		$where = $petalayer->getAdapter()->quoteInto('id_peta= ?', $id);
		$petalayer->delete($where);

		$where = $petagallery->getAdapter()->quoteInto('id= ?', $id);

		$petagallery->delete($where);
		@unlink(PETA_THUMBNAIL."/peta-".$id.".png");
	}

	public function addPetaLayer($id, $layers){
		$urutan = 1;
		$petalayer= new Petalayer();
		$cswlayer= new Cswlayer();
		$wmslayer= new Wmslayer();

		// delete current list
		$where = $petalayer->getAdapter()->quoteInto('id_peta= ?', $id);
		$petalayer->delete($where);

		//add layer list
		foreach($layers as $l){
			if(!isset($l["identifier"])){
				$row = $wmslayer->createRow();
				$row->title = $l["title"];
				$row->nama_layer = $l["params"]["LAYERS"];
				$row->srs = isset($l["params"]["SRS"])?$l["params"]["SRS"]:"EPSG:4326";
				$row->x_min = 0;
				$row->y_min = 0;
				$row->x_max = 0;
				$row->y_max = 0;
				$row->url = $l["url"];
				$idwmslayer = $row->save();
				$idcswlayer = 0;
			}else{
				$datacswlayer = $cswlayer->fetchRow("identifier='".$l["identifier"]."'");
				$idcswlayer = $datacswlayer->id;
				$idwmslayer = 0;
			}
			$row = $petalayer->createRow();
			$row->id_peta = $id;
			$row->id_csw_layer = $idcswlayer;
			$row->id_wms_layer = $idwmslayer;
			$row->urutan = $urutan;
			$row->on_off = 1;
			$row->save();
			$urutan++;
		}
	}

	public function addMemberDokumen($judul, $konten, $id_kategori, $id_peta=0){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$dokumen = new Dokumen();
		$row  = $dokumen->createRow();
		$row->judul 	= $judul;
		$row->author 	= "-";
		$row->konten 	= $konten;
		$row->time_created 	= date('Y-m-d G:i:s');
		$row->id_kategori 	= $id_kategori;
		$row->member_creator 	= $member_auth->username;
		$row->id_peta 	= $id_peta;
		$id_dokumen = $row->save();

		if($id_dokumen){
			$upload = $this->addMemberDokumenFile($id_dokumen);
			if ($upload){
				$result["success"]= true;
			}else{
				$result["success"]= false;
				$result["reason"] = "Data dokumen berhasil ditambahkan tetapi gagal mengupload file. Silahkan lakukan upload file kembali pada dokumen.";
			}
		}else{
			$result["success"]= false;
			$result["reason"] = "Gagal menambahkan dokumen.";
		}
		return $result;				
	}

	public function addMemberDokumenFile($id_dokumen){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$dokumenfile = new Dokumenfile();

		$upload = new Zend_File_Transfer_Adapter_Http();

		$upload->setDestination(DOKUMEN_FILE)->addValidator('Extension', false, array('doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'jpg', 'png', 'tiff', 'tif'));
		
		$files  = $upload->getFileInfo();

		foreach($files as $file=>$info){
			if ($upload->isUploaded($file)) {
				if ($upload->isValid($file)) {
					$nama_file = $upload->getFileName($file);
					$file_ext = pathinfo($nama_file, PATHINFO_EXTENSION);
					$nama_file_renamed = md5(time()).".".$file_ext;
					$upload->addFilter(new Zend_Filter_File_Rename(array('target' => DOKUMEN_FILE."/".$nama_file_renamed, 'overwrite' => true)), null, $file);
					if($upload->receive($file)){
						$dokumenfilerow                    = $dokumenfile->createRow();
						$dokumenfilerow->nama_file         = basename($nama_file);
						$dokumenfilerow->nama_file_renamed = $nama_file_renamed;
						$dokumenfilerow->path_relatif      = DOKUMEN_FILE."/".$nama_file_renamed;
						$dokumenfilerow->content_type      = $upload->getMimeType($file);
						$dokumenfilerow->file_size         = filesize(DOKUMEN_FILE."/".$nama_file_renamed);
						$dokumenfilerow->waktu_upload      = date('Y-m-d G:i:s');
						$dokumenfilerow->id_dokumen        = $id_dokumen;
						$dokumenfilerow->user_creator      = $member_auth->username;
						$dokumenfilerow->save();
					}else{
						return false;
					}
				}
			}
		}
		return true;
	}

	public function updateMemberDokumen($id_dokumen, $judul, $konten, $id_kategori, $id_peta=0){
		$member_auth = new Zend_Session_Namespace('member_auth');
		$dokumen = new Dokumen();

		$data['judul'] =$judul;
		$data['author'] ="-";
		$data['konten'] =$konten;
		$data['id_kategori'] =$id_kategori;
		$data['id_peta'] =$id_peta;
		$data['member_creator'] =$member_auth->username;
		
		$where = $dokumen->getAdapter()->quoteInto('id= ?', $id_dokumen);
		$dokumen->update($data, $where);

		// $this->addMemberDokumenFile($id_dokumen);
		$result["success"]= true;
		return $result;
	}

	public function deleteMemberDokumen($id_dokumen){
		$dokumen = new Dokumen();

		$files = $this->web->getDocFiles($id_dokumen);

		foreach($files as $file){
			$this->deleteMemberDokumenFile($file["id_file"]);
		}

		$where = $dokumen->getAdapter()->quoteInto('id= ?', $id_dokumen);
		$dokumen->delete($where);

		$this->addMemberDokumenFile($id_dokumen);
		return true;
	}

	public function deleteMemberDokumenFile($id_dokumen_file){
		$dokumenfile = new Dokumenfile();

		$where = $dokumenfile->getAdapter()->quoteInto('id= ?', $id_dokumen_file);
		$data = $dokumenfile->fetchRow($where);
		$file = DOKUMEN_FILE."/".$data->nama_file_renamed;
		if(file_exists($file)){
			@unlink($file);
		}
		$dokumenfile->delete($where);
		return true;		
	}

	public function savePetaThumbnail($id, $layers, $xmin, $ymin, $xmax, $ymax){
		$layers = array_reverse($layers);
		$i=0;
		$width = 1024;
		$xRatio = abs($xmax-$xmin);
		$yRatio = abs($ymax-$ymin);
		$height = floor($width*($yRatio/$xRatio));

		$baselayer = array(
						"url"=> "http://ows.terrestris.de/osm/service?",
						"layers"=> "OSM-WMS",
						"versions"=> "1.1.1",
						"styles" => "",
						"srs"=> "EPSG:4326"
					); 
		
		$wmsURL = substr($baselayer["url"], -1)=="?"?substr($baselayer["url"],0,-1):$baselayer["url"];
		$url = $wmsURL."?service=WMS&version=1.1.1&request=GetMap&layers=".$baselayer["layers"]."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&srs=".$baselayer["srs"]."&format=image/png&styles=".$baselayer["styles"]."&transparent=true";
		error_log($url);
		$handle = fopen($url, 'rb');
		$thumbnail = new Imagick();
		$thumbnail->readImageFile($handle);
		fclose($handle);

		foreach($layers as $l){
			$wmsURL = substr($l["url"], -1)=="?"?substr($l["url"],0,-1):$l["url"];
			$url = $wmsURL."?service=WMS&version=1.1.1&request=GetMap&layers=".$l["params"]["LAYERS"]."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&SRS=".(isset($l["params"]["SRS"])?$l["params"]["SRS"]:"EPSG:4326")."&format=image/png&styles=&transparent=true";
			error_log($url);
			$handle = fopen($url, 'rb');
			$img = new Imagick();
			$img->readImageFile($handle);
			$thumbnail->compositeImage( $img, imagick::COMPOSITE_DEFAULT, 0, 0 );
			fclose($handle);
			$i++;
		}
		#$thumbnail->writeImage(PETA_THUMBNAIL."/peta-".$id.".png");
			#hnr: 
			$fileresized=PETA_THUMBNAIL."/peta-".$id.".png";
			$this->writeImageHelper($fileresized,$thumbnail);
	}

	/* hnr: ini imagemagic  ga bisa create file yang ada tanda titik dua nya di redhat 6 */
	public function writeImageHelper($filename,$img){
		$temp_file="/tmp/sementara.png";
		@unlink($temp_file);
		$img->writeImage($temp_file);
		rename($temp_file,$filename);
	}
	public function getMembersName($username){
		$member = new Member();
		$data = $member->fetchRow("name='$username'");
		return $data->nama_lengkap; 
	}
}
?>
