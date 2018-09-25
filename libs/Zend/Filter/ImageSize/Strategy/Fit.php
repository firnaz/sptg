<?php

/**
 * @see Zend_Filter_ImageSize_Strategy_Interface 
 */
require_once 'Zend/Filter/ImageSize/Strategy/Interface.php';

/**
 * Strategy for resizing the image by fitting the content into the given 
 * dimensions.
 */
class Zend_Filter_ImageSize_Strategy_Fit 
    implements Zend_Filter_ImageSize_Strategy_Interface
{
    /**
     * Return canvas resized according to the given dimensions.
     * @param resource $image GD image resource
     * @param int $width Output width
     * @param int $height Output height
     * @return resource GD image resource
     */
    private $_name = "Fit";
    public function getName(){
        return $this->_name;
    } 
    public function resize($image, $width, $height)
    {
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);
        
        $rWidth = ceil($origWidth / $width);
        $rHeight = ceil($origHeight / $height);

        if($rWidth > $rHeight) {
            $ratio = $rWidth;
        } else {
            $ratio = $rHeight;
        }
        
        $newWidth = $origWidth / $ratio;
        $newHeight = $origHeight / $ratio;    
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        
        return $resized;
    }
}