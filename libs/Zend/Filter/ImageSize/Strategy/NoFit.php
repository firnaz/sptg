<?php

/**
 * @see Zend_Filter_ImageSize_Strategy_Interface 
 */
require_once 'Zend/Filter/ImageSize/Strategy/Interface.php';

/**
 * Strategy for resizing the image so that its smalles edge fits into the frame.
 * The rest is cropped.
 */
class Zend_Filter_ImageSize_Strategy_NoFit
    implements Zend_Filter_ImageSize_Strategy_Interface
{
    /**
     * Return canvas resized according to the given dimensions.
     * @param resource $image GD image resource
     * @param int $width Output width
     * @param int $height Output height
     * @return resource GD image resource
     */
    private $_name = "PaddedResize";
    public function getName(){
        return $this->_name;
    } 
    public function resize($image, $width, $height)
    {
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);
                        
        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $bg = $this->color_web2gd($resized, "FFFFFF");
        imagefilledrectangle($resized, 0, 0, $width, $height, $bg);

        $destAR = $width / $height;
        if ($origWidth > 0 && $origHeight > 0) {
            // We can't divide by zero theres something wrong.
            
            $srcAR = $origWidth / $origHeight;
        
            // Destination narrower than the source
            if($destAR > $srcAR) {
                $destY = 0;
                $destHeight = $height;
                
                $destWidth = $height * $srcAR;
                $destX = ($width - $destWidth) / 2;
            
            // Destination shorter than the source
            } else {
                $destX = 0;
                $destWidth = $width;
                
                $destHeight = $width / $srcAR;
                $destY = ($height - $destHeight) / 2;
            }
            
        }           
        imagecopyresampled($resized, $image, $destX, $destY, 0, 0, $destWidth, $destHeight, $origWidth, $origHeight);
        //imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
        
        return $resized;
    }
    public function color_web2gd($image, $webColor) {
        if(substr($webColor,0,1) == "#") $webColor = substr($webColor,1);
        $r = hexdec(substr($webColor,0,2));
        $g = hexdec(substr($webColor,2,2));
        $b = hexdec(substr($webColor,4,2));
        
        return imagecolorallocate($image, $r, $g, $b);
        
    }
}