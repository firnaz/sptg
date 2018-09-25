<?php
class Geoserver extends Zend_Controller_Action_Helper_Abstract {
	private $url="";
	private $rest_url = "";
	private $datastore = "";
	private $workspaces = "";
	private $user = "";
	private $password = "";
	private $db;

	public function __construct($url, $ws, $ds, $uname, $pwd){
		$this->url = $url;
		$this->rest_url = $this->url."/rest";
		$this->workspaces = $ws;
		$this->datastore = $ds;
		$this->user = $uname;
		$this->password = $pwd;


     	if(!Zend_Registry::isRegistered('dbGeoserver')){
			$parameters = array(
	                'host'     => PGSQL_HOST,
	                'username' => PGSQL_USER,
	                'password' => PGSQL_PWD,
	                'dbname'   => $this->datastore
	               );		
			try {
			    $db = Zend_Db::factory('PDO_PGSQL', $parameters);
			    $db->getConnection();
			    $this->db = $db;
				Zend_Registry::set('dbGeoserver', $db);
			} catch (Zend_Db_Adapter_Exception $e) {
			    echo $e->getMessage();
			    die('Tidak dapat terhubung ke database.');
			} catch (Zend_Exception $e) {
			    echo $e->getMessage();
			    die('Tidak dapat terhubung ke database.');
			}			
		}else{
			$db = Zend_Registry::get('dbGeoserver');
		    $this->db = $db;
		}
	}

	public function extractFeature($zip_file){
		$filename = substr(basename($zip_file),0,-4).date("_YmdHis");
		$filename = strtolower($filename);
		$filename = str_replace("-", "_", $filename);
		$filename = str_replace(" ", "_", $filename);
		$dir= TMP_DIR."/shp/".$filename;
		$zip = new ZipArchive; 
		mkdir($dir);
		if ( $zip->open( $zip_file ) )
    	{
			for ( $i=0; $i < $zip->numFiles; $i++ )
			{ 
				$entry_name = $zip->getNameIndex($i);
				if(substr($entry_name,0,2)=="__"){
					continue;
				}
				if(substr(basename($entry_name),-3,3)=="shp" || substr(basename($entry_name),-3,3)=="dbf" || substr(basename($entry_name),-3,3)=="shx" || substr(basename($entry_name),-3,3)=="prj" || substr(basename($entry_name),-3,3)=="sld"){
					$entries[] = $entry_name;
					$files[substr(basename($entry_name),-3,3)]= $entry_name;
				}
			}
			$zip->extractTo($dir,$entries);
		}
		return array("entries"=>$files, "tablename"=>$filename, "dirname"=>$dir);
	}

	public function validateFeature($zip_file){
		$zip = new ZipArchive; 
		$shp = false;
		$dbf=false;
		$shx=false;
		$prj=false;
		$sld=false;
		//echo $zip_file;
		
		if ( $zip->open($zip_file) )
    	{
			for ( $i=0; $i < $zip->numFiles; $i++ )
			{ 
				$entry_name = $zip->getNameIndex($i);
				//echo $entry_name;
				if(substr($entry_name,0,2)=="__"){
					continue;
				}
				if(substr(basename($entry_name),-3,3)=="shp"){
					$shp=true;
				}
				if(substr(basename($entry_name),-3,3)=="dbf"){
					$dbf=true;
				}
				if(substr(basename($entry_name),-3,3)=="shx"){
					$shx=true;
				}
				if(substr(basename($entry_name),-3,3)=="prj"){
					$prj=true;
				}
				if(substr(basename($entry_name),-3,3)=="sld"){
					$sld=true;
				}
			}
		}
		if($shp && $dbf && $shx && $prj && $sld){
			return true;
		}else {
			return false;
		}
	}

	public function addFeature($title, $abstract, $zip_file, $username="", $password=""){
		// validate zip file
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		if($this->validateFeature($zip_file)){
			$config = new Zend_Config_Ini(APPLICATION_PATH . '/config/app.ini', APPLICATION_ENV);

			// extract zip file
			$result = $this->extractFeature($zip_file);
			// print_r($result["dirname"]."/".$result["entries"]["sld"]);exit;

			// get epsg
			$getepsgcmd = GETEPSG." ".$result["dirname"]."/".$result["entries"]["prj"];
			error_log($getepsgcmd);
			exec($getepsgcmd, $epsg);

			// shp to postgis
			$ogr2ogrcmd = OGR2OGR.' -overwrite -f "PostgreSQL" PG:"host='.PGSQL_HOST.' user='.PGSQL_USER.' dbname='.$this->datastore.' password='.PGSQL_PWD.'" '.$result["dirname"]."/".$result["entries"]["shp"].' -nln '.$result["tablename"].' -a_srs EPSG:'.$epsg[0].' -nlt PROMOTE_TO_MULTI -skipfailures';
			error_log($ogr2ogrcmd);
			// echo $ogr2ogrcmd;exit;
			exec($ogr2ogrcmd);

			// get extent
			$sql = "select ST_Extent(wkb_geometry) from ".$result["tablename"];

			$row = $this->db->fetchRow($sql);
			if($row["st_extent"]){
				$bbox = str_replace("BOX(","",$row["st_extent"]);
				$bbox = str_replace(")","",$bbox);
				$corner = explode(",", $bbox);
				$lowercorner = explode(" ",$corner[0]);
				$uppercorner = explode(" ",$corner[1]);
				$extent = array("xmin"=>$lowercorner[0], "ymin"=>$lowercorner[1], "xmax"=>$uppercorner[0], "ymax"=>$uppercorner[1]);

				// // add feature to geoserver
				$xml = "<featureType><title>".$title."</title><abstract>".$abstract."</abstract><name>".$result["tablename"]."</name></featureType>";
				$addfeatureurl = $this->rest_url."/workspaces/".$this->workspaces."/datastores/".$this->datastore."/featuretypes";
				$client = new Zend_Http_Client($addfeatureurl);
				$client->setConfig(array('timeout'=>30));
				$client->setAuth($username, $password);
				$response = $client->setRawData($xml, 'application/xml')->request('POST');
				error_log($response->getBody());

				// //add style to geoserver
				$this->addStyle($result["tablename"]."_style", $result["dirname"]."/".$result["entries"]["sld"], $result["tablename"], $username, $password);

				$Sipitung = Zend_Controller_Action_HelperBroker::getStaticHelper('Sipitung');

				//$Sipitung->deleteDir($result["dirname"]);
				$this->createThumbnail($this->workspaces.":".$result["tablename"], 1024, "EPSG:".$epsg[0], $extent["xmin"], $extent["ymin"], $extent["xmax"], $extent["ymax"]);
				return array("extent"=>$extent, "success"=>true, "entries"=>$result["entries"], "name"=>$result["tablename"], "dirname"=>$result["dirname"], "epsg"=>$epsg[0]);
			}else{
				return array("success"=>false, "reason"=>"Terdapat error pada file yang anda upload!");
			}
		}else{
			return array("success"=>false, "reason"=>"File tidak valid!");
		}
	}

	public function updateFeature($title, $abstract, $feature, $username="", $password=""){
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		$xml = "<featureType><title>".$title."</title><abstract>".$abstract."</abstract><name>".$feature."</name><enabled>true</enabled></featureType>";
		$editfeatureurl = $this->rest_url."/workspaces/".$this->workspaces."/datastores/".$this->datastore."/featuretypes/".$feature;

		$client = new Zend_Http_Client($editfeatureurl);
		$client->setConfig(array('timeout'=>30));
		$client->setAuth($username, $password);
		$response = $client->setRawData($xml, 'application/xml')->request('PUT');
		error_log($response->getBody());
	}

	public function deleteFeature($feature, $username="",$password=""){
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		$deletefeatureurl = $this->rest_url."/workspaces/".$this->workspaces."/datastores/".$this->datastore."/featuretypes/".$feature."?recurse=true";

		$client = new Zend_Http_Client($deletefeatureurl);
		$client->setConfig(array('timeout'=>30));
		$client->setAuth($username, $password);
		$response = $client->request('DELETE');

		// delete table from postgresql
		$sql = "DROP TABLE IF EXISTS $feature";
		$this->db->query($sql);

		// delete thumbnail
		@unlink(LAYER_THUMBNAIL."/".$feature.".png");
	}

	public function extractCoverage($zip_file){
		$filename = substr(basename($zip_file),0,-4).date("_YmdHis");
		$dir= TMP_DIR."/shp/".$filename;
		$zip = new ZipArchive; 
		mkdir($dir);
		if ( $zip->open( $zip_file ) )
    	{
			for ( $i=0; $i < $zip->numFiles; $i++ )
			{ 
				$entry_name = $zip->getNameIndex($i);
				if(substr($entry_name,0,2)=="__"){
					continue;
				}
				if(substr(basename($entry_name),-3,3)=="tif" || substr(basename($entry_name),-4,4)=="tiff"){
					$entries[] = $entry_name;
					$files["tif"]= $entry_name;
				}
				if(substr(basename($entry_name),-3,3)=="sld"){
					$entries[] = $entry_name;
					$files["sld"]= $entry_name;
				}
			}
			$zip->extractTo($dir,$entries);
		}
		return array("entries"=>$files, "tablename"=>$filename, "dirname"=>$dir);
	}

	public function validateCoverage($zip_file){
		$zip = new ZipArchive; 
		$tif=false;
		$sld=false;
		//echo $zip_file;
		
		if ( $zip->open($zip_file) )
    	{
			for ( $i=0; $i < $zip->numFiles; $i++ )
			{ 
				$entry_name = $zip->getNameIndex($i);
				if(substr($entry_name,0,2)=="__"){
					continue;
				}
				if(substr(basename($entry_name),-3,3)=="tif" || substr(basename($entry_name),-4,4)=="tiff"){
					$tif=true;
				}
				if(substr(basename($entry_name),-3,3)=="sld"){
					$sld=true;
				}
			}
		}
		if($tif && $sld){
			return true;
		}else {
			return false;
		}
	}

	public function addCoverage($title, $abstract, $zip_file, $username="", $password=""){
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		if($this->validateCoverage($zip_file)){
			// extract zip file
			$result = $this->extractCoverage($zip_file);
			$raster_file = $result["dirname"]."/".$result["entries"]['tif'];
			$ext = pathinfo($raster_file, PATHINFO_EXTENSION); 

			$name = str_replace(".$ext","",basename($raster_file)).date("_YmdHis");

			$adapter = new Zend_Http_Client_Adapter_Curl();
			$client = new Zend_Http_Client();
			$client->setConfig(array('timeout'=>30));
			$client->setAuth($username, $password);

			// get raster extent
			$getrasterextentcmd = GETRASTEREXTENT." ".$raster_file;
			error_log(getrasterextentcmd);
			exec($getrasterextentcmd, $bbox);
			$bbox = explode(" ",$bbox[0]);
			$extent = array("xmin"=>$bbox[0], "ymin"=>$bbox[1], "xmax"=>$bbox[2], "ymax"=>$bbox[3]);

			// get epsg
			$getepsgcmd = GETRASTEREPSG." ".$raster_file;
			error_log(getepsgcmd);
			exec($getepsgcmd, $epsg);
	 
	 		// create coveragestores
			$addcoveragestoresurl = $this->rest_url."/workspaces/".$this->workspaces."/coveragestores";
			$xml = "<coverageStore><name>$name</name><workspace>".$this->workspaces."</workspace><enabled>true</enabled></coverageStore>";
			$client->setUri($addcoveragestoresurl);
			// $client->setHeaders('Content-type', 'application/xml');

			$response = $client->setRawData($xml, 'application/xml')->request('POST');


			// put tiff file
			$puttiffurl = $this->rest_url."/workspaces/".$this->workspaces."/coveragestores/$name/file.geotiff?configure=first&coverageName=$name";
			$client->setUri($puttiffurl);
			$fp = fopen($raster_file,'r');
			$response = $client->setRawData($fp, 'image/tiff')->request('PUT');

			// add coverage layer
			// $addcoverageurl = $this->rest_url."/workspaces/".$this->workspaces."/coveragestores/$name/coverages";
			// $xml = "<coverage><name>$name</name></coverage>";
			// $client->setUri($addcoverageurl);
			// $response = $client->setRawData($xml, 'application/xml')->request('POST');
			// print_r($response->getBody());exit;

			// //add style to geoserver
			$this->updateCoverage($title, $abstract, $name, $username, $password);

			$this->addStyle($name."_style", $result["dirname"]."/".$result["entries"]["sld"], $name, $username, $password);

			$this->createThumbnail($this->workspaces.":".$name, 1024, "EPSG:".$epsg[0], $extent["xmin"], $extent["ymin"], $extent["xmax"], $extent["ymax"]);
			@unlink($raster_file);
			return array("extent"=>$extent, "success"=>true, "name"=>$name, "epsg"=>$epsg[0]);
		}else{
			return array("success"=>false, "reason"=>"File tidak valid!");
		}
	}

	public function updateCoverage($title, $abstract, $coverage, $username="", $password=""){
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		$xml = "<coverage><title>".$title."</title><abstract>".$abstract."</abstract><name>".$coverage."</name><enabled>true</enabled></coverage>";
		$editcoverageurl = $this->rest_url."/workspaces/".$this->workspaces."/coveragestores/".$coverage."/coverages/".$coverage;

		$client = new Zend_Http_Client($editcoverageurl);
		$client->setConfig(array('timeout'=>30));
		$client->setAuth($username, $password);
		$response = $client->setRawData($xml, 'application/xml')->request('PUT');
		error_log($response->getBody());
	}
	
	public function deleteCoverage($coverage, $username="", $password=""){
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		$deletecoverageurl = $this->rest_url."/workspaces/".$this->workspaces."/coveragestores/".$coverage."?recurse=true&purge=all";

		$client = new Zend_Http_Client($deletecoverageurl);
		$client->setConfig(array('timeout'=>30));
		$client->setAuth($username, $password);
		$response = $client->request('DELETE');

		// delete thumbnail
		@unlink(LAYER_THUMBNAIL."/".$coverage.".png");
	}

	public function addStyle($name, $file, $layer, $username="", $password=""){
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		$client = new Zend_Http_Client();
		$client->setConfig(array('timeout'=>30));
		$client->setAuth($username, $password);

		$xml = "<style><name>$name</name><filename>$name.sld</filename></style>";
		$addstyleurl = $this->rest_url."/styles";
		$client->setUri($addstyleurl);
		$response = $client->setRawData($xml, 'application/xml')->request('POST');
		error_log($response->getBody());

		$putstyleurl = $this->rest_url."/styles/$name.sld";
		$client->setUri($putstyleurl);
		$sld = file_get_contents($file); 
		// $sld = strtolower($sld);
		$sld = str_replace("the_geom", "wkb_geometry", $sld);
		$sld = $this->renameProperty($sld);
		$response = $client->setRawData($sld, 'application/vnd.ogc.sld+xml')->request('PUT');
		error_log($response->getBody());

		// //set style to layer
		$xml = "<layer><defaultStyle><name>$name</name></defaultStyle></layer>";
		$setstyleurl = $this->rest_url."/layers/".$this->workspaces.":".$layer;
		$client->setUri($setstyleurl);
		$response = $client->setRawData($xml, 'text/xml')->request('PUT');
		error_log($response->getBody());
	}

	public function deleteStyle($style, $username="", $password=""){
		if(!$username){
			$username = $this->user;
		}
		if(!$password){
			$password = $this->password;
		}
		$deletestyleurl = $this->rest_url."/styles/$style.sld";

		$client = new Zend_Http_Client($deletestyleurl);
		$client->setConfig(array('timeout'=>30));
		$client->setAuth($username, $password);
		$response = $client->request('DELETE');
	}
	public function renameProperty($string, $tag="ogc:PropertyName"){
		return preg_replace_callback('#(<'.$tag.'[^>]*>)([^<]*)(</'.$tag.'[^>]*>)#i',
			function ($matches) { 
				return $matches[1].strtolower($matches[2]).$matches[3];
		}, $string);
	}
	public function createThumbnail($layer_name, $width, $srs, $xmin, $ymin, $xmax, $ymax){
		$xRatio = abs($xmax-$xmin);
		$yRatio = abs($ymax-$ymin);
		$height = floor($width*($yRatio/$xRatio));

		$url = $this->url."/wms?service=WMS&version=1.1.0&request=GetMap&layers=".$layer_name."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&srs=".$srs."&format=image/png";
		// $client = new Zend_Http_Client();
	    // $client->setUri($url);
	    // $result = $client->request('GET');        
	    // $img = imagecreatefromstring($result->getBody());
	    // imagepng($img,LAYER_THUMBNAIL.'/'.$layer_name.'.png');
	    // imagedestroy($img);
		@$handle = fopen($url, 'rb');
		//echo is_resource($handle);exit;
		if(is_resource($handle)){
			$img = new Imagick();
			try{
				$img->readImageFile($handle);
				#hnr: imagemagick ga bisa create file yang ada tanda titik dua nya di redhat 6
				$new_file=LAYER_THUMBNAIL.'/'.$layer_name.'.png';
				
				#$temp_file = "/tmp/sementara.png";
				#@unlink($temp_file);
				#$img->writeImage($temp_file);
				$img->writeImage($new_file);

				#rename($temp_file,$new_file);

				// $this->writeImageHelper($new_file,$img);
			} catch(Exception $e){
				// continue;
			}
			fclose($handle);
		}
	}

	/* hnr: ini imagemagic  ga bisa create file yang ada tanda titik dua nya di redhat 6 */
	public function writeImageHelper($filename,$img){
		$temp_file="/tmp/sementara.png";
		@unlink($temp_file);
		$img->writeImage($temp_file);
		rename($temp_file,$filename);
	}
}
?>
