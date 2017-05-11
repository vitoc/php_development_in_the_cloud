<?php
//require_once 'config.inc.php';
/**
 * Manages searches within PictureMe S3 storage
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt 
 */
class SearchManager extends Manager
{   
    /**
     * Returns an array of file names and their corresponding color locations found
     * based on RGB values and proximity specified.
     *
     * @param int $proximity
     * @param int $r     
     * @param int $g
     * @param int $b
     *
     * @return string
     */
    public function searchColors($proximity, $r, $g, $b) 
    {
        if (!$this->filterColor($r, $g, $b)) {
            return 'Primary color specified is not valid';
        }
        $pixel      = new Gmagick_FriendlyPixel("rgb({$r},{$g},{$b})");
        $neighbors  = $pixel->getAxesNeighbor($proximity);
        $searchTime = microtime();
        foreach ($neighbors as $neighbor) {
            $locations = $this->_localStore->get('('.implode(',', $neighbor).')');
            if ($locations !== NULL) {
                foreach (explode('|', $locations) as $location) {
                    $file                                    = substr($location, 0, strpos($location, '['));
                    $position                                = strstr($location, '[');
                    $colorPosition["{$position}"]            = '('.implode(',', $neighbor).')';
                    $locationColors["{$file}|{$searchTime}"] =  $colorPosition;
                    $fileColors["{$file}|{$searchTime}"]     = serialize($colorPosition);
                }
                $this->_localStore->put($fileColors);                
            }
        }
        if (empty($locationColors)) {
            return false;
        } else {
            uasort($locationColors, array('SearchManager', 'locationCompare'));        
        }
        return $locationColors;
    }
    /**
     * Compares pictures by the time it is stored in the bucket.
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    static function locationCompare($a, $b)
    {
        if (sizeof($a) == sizeof($b)) {
            return 0;
        }
        return (sizeof($a) > sizeof($b)) ? -1 : +1;        
    }
    /**
     * Filters invalid color values
     *
     * @param int $r
     * @param int $g
     * @param int $b
     *     
     * @return bool
     */
    public function filterColor($r, $g, $b)
    {
        $color = array ($r, $g, $b);
        foreach ($color as $primary) {
            if ((int)$primary < 0 || (int)$primary > 255) {
                return false;
            }
        }
        return true;
    }
    /**
     * Retrieve indexes from S3 storage and place it in local key-value store 
     */
    public function updateIndex()
    {
        $results    = '';
        $indexFiles = $this->_storage->getBucket(COLOR_INDEX_BUCKET);
        if ($indexFiles === false) {
            return 'No index files found';
        } else {
            $this->_localStore->vanish();        
        }
        foreach ($indexFiles as $name => $info) {
            $index               = $this->_storage->getObject(COLOR_INDEX_BUCKET, $name);
            $colorLocationsArray = explode(PHP_EOL, $index->body);
            foreach ($colorLocationsArray as $colorLocations) {
                $colorLocations          = trim($colorLocations);
                list($color, $locations) = explode(chr(9), $colorLocations);
                if (!empty($locations) && !empty($locations)) {
                    $storedColors["{$color}"] = $locations;
                }
            }
            $this->_localStore->put($storedColors);                            
        }
        return 'Index updating completed successfully'; 
    }
}
