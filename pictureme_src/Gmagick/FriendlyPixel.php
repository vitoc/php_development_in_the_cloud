<?php
/**
 * GmagickPixel class with extended neighbor awareness capabilities
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
class Gmagick_FriendlyPixel extends GmagickPixel
{
    /**
     * Returns neighbors from all axes of the pixel in a 3D colorspace as far as 
     * those that are within the proximity specified.
     *
     * @param int $proximity
     *
     * @return array
     */
    public function getAxesNeighbor($proximity)
    {
        $neighbours = array();
        $rgb        = $this->getColor(true);
        $edge['r']  = ($rgb['r'] - $proximity) < 1 ? 0 : $rgb['r'] - $proximity;        
        $edge['g']  = ($rgb['g'] - $proximity) < 1 ? 0 : $rgb['g'] - $proximity;        
        $edge['b']  = ($rgb['b'] - $proximity) < 1 ? 0 : $rgb['b'] - $proximity;                        
        for ($i = 0; $i < ($proximity * 2); $i++) {
            for ($j = 0; $j < ($proximity * 2); $j++) {
                for ($k = 0; $k < ($proximity * 2); $k++) {
                    $newPoint      = array();
                    $newPoint['r'] = (int)$edge['r'] + $i;
                    $newPoint['g'] = (int)$edge['g'] + $j;
                    $newPoint['b'] = (int)$edge['b'] + $k;                        
                    $neighbours[]  = $newPoint;
                }
            }
        }
        return $neighbours;
    }
}
