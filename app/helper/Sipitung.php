<?php
class Sipitung extends Zend_Controller_Action_Helper_Abstract {
	private $config;
	public function __construct(){
		$conf = Zend_Registry::get('config');
		$this->config=$conf;
	}
	public function deleteDir($dir) {  
	    $iterator = new RecursiveDirectoryIterator($dir);  
	    foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {  
	      if ($file->isDir()) {  
	         @rmdir($file->getPathname());  
	      } else {  
	         @unlink($file->getPathname());  
	      }  
	    }  
	    rmdir($dir);  
	}
	
	// xml2array function get from http://www.bin-co.com/php/scripts/xml2array/ 
	public function xml2array($contents, $get_attributes=1, $priority = 'tag') { 
	    if(!$contents) return array(); 

	    if(!function_exists('xml_parser_create')) { 
	        //print "'xml_parser_create()' function not found!"; 
	        return array(); 
	    } 

	    //Get the XML parser of PHP - PHP must have this module for the parser to work 
	    $parser = xml_parser_create(''); 
	    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss 
	    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
	    xml_parse_into_struct($parser, trim($contents), $xml_values); 
	    xml_parser_free($parser); 

	    if(!$xml_values) return;//Hmm... 

	    //Initializations 
	    $xml_array = array(); 
	    $parents = array(); 
	    $opened_tags = array(); 
	    $arr = array(); 

	    $current = &$xml_array; //Refference 

	    //Go through the tags. 
	    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array 
	    foreach($xml_values as $data) { 
	        unset($attributes,$value);//Remove existing values, or there will be trouble 

	        //This command will extract these variables into the foreach scope 
	        // tag(string), type(string), level(int), attributes(array). 
	        extract($data);//We could use the array by itself, but this cooler. 

	        $result = array(); 
	        $attributes_data = array(); 
	         
	        if(isset($value)) { 
	            if($priority == 'tag') $result = $value; 
	            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode 
	        } 

	        //Set the attributes too. 
	        if(isset($attributes) and $get_attributes) { 
	            foreach($attributes as $attr => $val) { 
	                if($priority == 'tag') $attributes_data[$attr] = $val; 
	                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr' 
	            } 
	        } 

	        //See tag status and do the needed. 
	        if($type == "open") {//The starting of the tag '<tag>' 
	            $parent[$level-1] = &$current; 
	            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag 
	                $current[$tag] = $result; 
	                if($attributes_data) $current[$tag. '_attr'] = $attributes_data; 
	                $repeated_tag_index[$tag.'_'.$level] = 1; 

	                $current = &$current[$tag]; 

	            } else { //There was another element with the same tag name 

	                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array 
	                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result; 
	                    $repeated_tag_index[$tag.'_'.$level]++; 
	                } else {//This section will make the value an array if multiple tags with the same name appear together 
	                    $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
	                    $repeated_tag_index[$tag.'_'.$level] = 2; 
	                     
	                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well 
	                        $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
	                        unset($current[$tag.'_attr']); 
	                    } 

	                } 
	                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1; 
	                $current = &$current[$tag][$last_item_index]; 
	            } 

	        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />' 
	            //See if the key is already taken. 
	            if(!isset($current[$tag])) { //New Key 
	                $current[$tag] = $result; 
	                $repeated_tag_index[$tag.'_'.$level] = 1; 
	                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data; 

	            } else { //If taken, put all things inside a list(array) 
	                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array... 

	                    // ...push the new element into that array. 
	                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result; 
	                     
	                    if($priority == 'tag' and $get_attributes and $attributes_data) { 
	                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data; 
	                    } 
	                    $repeated_tag_index[$tag.'_'.$level]++; 

	                } else { //If it is not an array... 
	                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
	                    $repeated_tag_index[$tag.'_'.$level] = 1; 
	                    if($priority == 'tag' and $get_attributes) { 
	                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well 
	                             
	                            $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
	                            unset($current[$tag.'_attr']); 
	                        } 
	                         
	                        if($attributes_data) { 
	                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data; 
	                        } 
	                    } 
	                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken 
	                } 
	            } 

	        } elseif($type == 'close') { //End of tag '</tag>' 
	            $current = &$parent[$level-1]; 
	        } 
	    } 
	     
	    return($xml_array); 
	}  
	function paddedresizeshow($file,$width=0,$height=0){
		$thumbdir = dirname($file)."/_resize";
		if(!file_exists($thumbdir)){
			@mkdir($thumbdir);
		}
		$md5 = md5_file($file);
		$fileresized = $thumbdir."/p_".$width."_".$height."_".$md5."_".strtolower(basename($file));
		if(!file_exists($fileresized)){
			$image = new Imagick($file);
			$image->thumbnailImage($width,$height,true,true); 
	        $image->writeImage($fileresized);
			#hnr: 
			// $this->writeImageHelper($fileresized,$image);
	    }
        $mime = mime_content_type($fileresized);
        $content = file_get_contents($fileresized);
		header("last-modified: " .gmstrftime("%a, %d %b %Y %T %Z", filemtime($fileresized)));
        header("Content-type: $mime");
        echo $content;
        exit;
	}
	function croppedresizeshow($file,$width=0,$height=0){
		$thumbdir = dirname($file)."/_resize";
		if(!file_exists($thumbdir)){
			@mkdir($thumbdir);
		}
		$md5 = md5_file($file);
		$fileresized = $thumbdir."/c_".$width."_".$height."_".$md5."_".strtolower(basename($file));
		if(!file_exists($fileresized)){
			$image = new Imagick($file);
			$image->cropThumbnailImage($width,$height);
			$image->writeImage($fileresized);
			#hnr: 
			// $this->writeImageHelper($fileresized,$image);
		}
        $mime = mime_content_type($fileresized);
        $content = file_get_contents($fileresized);
		header("last-modified: " .gmstrftime("%a, %d %b %Y %T %Z", filemtime($fileresized)));
        header("Content-type: $mime");
        echo $content;
        exit;
	}
	function imageshow($file){
		$content = file_get_contents($file);
        $mime = mime_content_type($file);
		header("last-modified: " .gmstrftime("%a, %d %b %Y %T %Z", filemtime($file)));
        header("Content-type: $mime");
        echo $content;
        exit;		
	}
	function generateURLSegment($title){
		$t = strtolower($title);
		$t = str_replace('&amp;','-dan-',$t);
		$t = str_replace('&','-dan-',$t);
		$t = preg_replace('/[^A-Za-z0-9]+/','-',$t);
		$t = preg_replace('/-+/','-',$t);
		return $t;
	}
	function checkAdminLogin(){
		$user_auth = new Zend_Session_Namespace('admin_auth');
		if ($user_auth->username){
			$result['time'] = $user_auth->time; 
			$result['username']= $user_auth->username;
			$result['success'] = "true";
		}else{
			$result= false; 
		}		
		return $result;
	}
	public function getLayersByPeta($id_peta){
		$pycsw = Zend_Controller_Action_HelperBroker::getStaticHelper('PyCswClient');
		$petalayer = new Petalayer();
		$cswlayer = new Cswlayer();
		$wmslayer = new Wmslayer();

		$datalayers = $petalayer->fetchAll($petalayer->select()->order("urutan ASC")->where("id_peta='".$id_peta."'"));
		$layers = array();
		foreach($datalayers as $datalayer){
			if($datalayer->id_csw_layer){
				$datacswlayer = $cswlayer->fetchRow($cswlayer->select()->where("id='".$datalayer->id_csw_layer."'"));
				$layers[] = $pycsw->getRecordsById($datacswlayer->identifier);
			}else{
				$datawmslayer = $wmslayer->fetchRow("id='".$datalayer->id_wms_layer."'");
				$layers[]= array(
					"abstract" 		=> $datawmslayer->title,
					"boundingbox"	=> $datawmslayer->x_min." ".$datawmslayer->y_min.", ".$datawmslayer->x_max." ".$datawmslayer->y_max,
					"creator" 		=> "",
					"format"		=> "",
					"identifier" 	=> "",
					"language" 		=> "",
					"layer" 		=> $datawmslayer->nama_layer,
					"modified" 		=> "",
					"srs" 			=> $datawmslayer->srs,
					"subject" 		=> "",
					"thumbnail" 	=> "",
					"tipe_layer" 	=> "",
					"title" 		=> $datawmslayer->title,
					"type" 			=> "external_wms",
					"url" 			=> $datawmslayer->url,
					"xmax" 			=> $datawmslayer->x_max,
					"xmin" 			=> $datawmslayer->x_min,
					"ymax" 			=> $datawmslayer->y_max,
					"ymin" 			=> $datawmslayer->y_min
				);
			}
		}
		return $layers;
	}
	public function getWmsLayer(){
		$wms= new Wms();
		$children= new Children();
		$datawms= $wms->fetchAll($wms->select()->order("nama ASC"));
		$datachildren= $children->fetchAll($children->select()->order("nama_sistem ASC"));

		$data = array();

		foreach($datawms as $row){
			$data[] = array(
					"nama"=>$row->nama,
					"url"=>$row->url,
					"kategori"=>"Eksternal WMS"
				);
		}

		foreach($datachildren as $row){
			$data[] = array(
					"nama"=>$row->nama_sistem,
					"url"=>$row->url_wms,
					"kategori"=>"Simpul SIH3"
				);
		}
		return $data;		
	}
	public function createPetaThumbnail($id, $xmin, $ymin, $xmax, $ymax){
		$pycsw = Zend_Controller_Action_HelperBroker::getStaticHelper('PyCswClient');
		if($xmin && $ymin && $xmax && $ymax){
			$petalayer = new Petalayer();
			$cswlayer = new Cswlayer();
			$wmslayer= new Wmslayer();
			$width = 1024;
			$xRatio = abs($xmax-$xmin);
			$yRatio = abs($ymax-$ymin);
			$height = floor($width*($yRatio/$xRatio));
			$i=0;
			$baselayer = array(
							"url"=> "http://ows.terrestris.de/osm/service?",
							"layers"=> "OSM-WMS",
							"versions"=> "1.1.1",
							"styles" => "",
							"srs"=> "EPSG:4326"
						); 
			
			$wmsURL = substr($baselayer["url"], -1)=="?"?substr($baselayer["url"],0,-1):$baselayer["url"];
			$url = $wmsURL."?service=WMS&version=1.1.1&request=GetMap&layers=".$baselayer["layers"]."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&srs=".$baselayer["srs"]."&format=image/png&styles=".$baselayer["styles"]."&transparent=true";
			@$handle = fopen($url, 'rb');
			if(is_resource($handle)){
				try{
					$thumbnail = new Imagick();
					$thumbnail->readImageFile($handle);
				}catch(Exception $e){
					$thumbnail = null;
				}
				fclose($handle);
			}
			$datapeta = $petalayer->fetchAll($petalayer->select()->order("urutan DESC")->where("id_peta='$id'"));
			foreach($datapeta as $peta){
				if($peta->id_csw_layer!=0){
					$datalayer = $cswlayer->fetchRow("id='".$peta->id_csw_layer."'");
					$metadata = $pycsw->getRecordsById($datalayer->identifier);
					$url = $this->config["geoserver.url"]."/wms?service=WMS&version=1.1.1&request=GetMap&layers=".$metadata["layer"]."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&srs=".$metadata["srs"]."&format=image/png&styles=&transparent=true";
					// print_r($url);exit;
				}else{
					$datalayer = $wmslayer->fetchRow("id='".$peta->id_wms_layer."'");
					$url = $datalayer->url."version=1.1.1&request=GetMap&layers=".$datalayer->nama_layer."&bbox=$xmin,$ymin,$xmax,$ymax&width=".$width."&height=".$height."&srs=".$datalayer->srs."&format=image/png&styles=&transparent=true";
				}
				@$handle = fopen($url, 'rb');
				if(is_resource($handle)){
					$img = new Imagick();
					try{
						$img->readImageFile($handle);
						if($thumbnail){
							$thumbnail->compositeImage( $img, imagick::COMPOSITE_DEFAULT, 0, 0 );
						}else{
							$thumbnail=$img;
						}
					}catch(Exception $e){
						continue;
					}
					fclose($handle);
				}
			}
			// if($thumbnail){
			// 	$thumbnail->writeImage(PETA_THUMBNAIL."/peta-".$id.".png");
			// }
			$filename=PETA_THUMBNAIL."/peta-".$id.".png";
			#hnr: 
			$this->writeImageHelper($filename,$thumbnail);
		}
	}

	/* hnr: ini imagemagic  ga bisa create file yang ada tanda titik dua nya di redhat 6 */
	/* firnas: bisa cuman imagemagicknya versinya harus diatas 6.6 */
	public function writeImageHelper($filename,$img){
		$temp_file="/tmp/sementara.png";
		@unlink($temp_file);
		try{
			$img->writeImage($temp_file);
		}catch(Exception $e){
			return;
		}
		rename($temp_file,$filename);
	}
}
?>
