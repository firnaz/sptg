<?php

/**
 * @see Zend_Filter_ImageSize_Strategy_Interface 
 */
require_once 'Zend/Filter/ImageSize/Strategy/Interface.php';

/**
 * Strategy for resizing the image so that its smalles edge fits into the frame.
 * The rest is cropped.
 */
class Zend_Filter_ImageSize_Strategy_Crop 
    implements Zend_Filter_ImageSize_Strategy_Interface
{
    /**
     * Return canvas resized according to the given dimensions.
     * @param resource $image GD image resource
     * @param int $width Output width
     * @param int $height Output height
     * @return resource GD image resource
     */
    private $_name = "Cropped"; 
    public function getName(){
        return $this->_name;
    } 
    public function resize($image, $width, $height)
    {
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);
        
        // $ratio = min(array($origWidth, $origHeight)) / max($width, $height);
        
        // $w = $origWidth * $ratio;
        // $h = $origHeight * $ratio;
   
        $width = round($width);
        $height = round($height);
       
        $cropped = imagecreatetruecolor($width, $height);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);

        $destAR = $width / $height;
        if ($origWidth > 0 && $origHeight > 0 ){
            // We can't divide by zero theres something wrong.
            
            $srcAR = $origWidth/ $origHeight;
        
            // Destination narrower than the source
            if($destAR < $srcAR) {
                $srcY = 0;
                $srcHeight = $origHeight;
                
                $srcWidth = $origHeight * $destAR;
                $srcX = ($origWidth - $srcWidth) / 2;
            
            // Destination shorter than the source
            } else {
                $srcX = 0;
                $srcWidth = $origWidth;
                
                $srcHeight = $origWidth / $destAR;
                $srcY = ($origHeight - $srcHeight) / 2;
            }
        }
//        imagecopyresampled($cropped, $image, 0, 0, 0, 0, $origWidth, $origHeight, $w, $h);
        imagecopyresampled($cropped, $image, 0,0, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);

        return $cropped;
    }
}