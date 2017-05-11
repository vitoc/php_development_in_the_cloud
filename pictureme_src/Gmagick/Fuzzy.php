<?php
/**
 * Gmagick class with extended fuzzy color grid capabilities
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
class Gmagick_Fuzzy extends Gmagick
{
    /**
     * Returns the fuzzy color grid of the instantiated image given a size of 
     * grid in pixels
     *
     * @param int $gridSize
     *
     * @return array
     */
    public function getFuzzyColorGrid($gridSize)
    {
        $colorGrid = array();
        for ($i = 0; $i < $this->getImageWidth(); $i += $gridSize) {
            for ($j = 0; $j < $this->getImageHeight(); $j += $gridSize) {
                $cropped   = clone $this;
                $histogram = $cropped->cropImage($gridSize, $gridSize, $i, $j)
                ->quantizeImage(1, Gmagick::COLORSPACE_RGB, 0, false, false)
                ->getImageHistogram();
                $colorGrid[$i][$j] = $histogram[0]->getColor();
            }
        }
        return $colorGrid;
    }
}
