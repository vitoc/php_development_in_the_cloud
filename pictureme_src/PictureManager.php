<?php
/**
 * Manages actions that can be performed with pictures.
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
class PictureManager extends Manager
{
    /**
     * Array of meta information of pictures
     *
     * @var array
     */
    protected $_pictureList = false;
    /**
     * GoogleMapAPI object
     *
     * @var GoogleMapAPI
     */
    protected $_map;
    /**
     * Array of picture information
     *
     * @var array
     */
    protected $_picture;
    /**
     * The S3 object
     *
     * @var S3
     */
    protected $_storage;
    public function __construct()
    {
        parent::__construct();
        $this->_storage = SIMPLECLOUD ? new CloudStorage(SIMPLECLOUD) : new S3(ACCESS_KEY, SECRET_KEY, false);
        if (extension_loaded('gearman')) {
            $this->_workerManager = new Distributed_WorkerManager;
            $this->_gearmanClient = new GearmanClient();
            $this->_gearmanClient->addServer();
        }
        $this->_map = new GoogleMapAPI('map');
        $this->_map->setAPIKey(GOOGLE_MAPS_API_KEY);
        $this->_map->setWidth(720); //annoyingly not chainable
        $this->_map->setHeight(200);
        $this->_refreshList();
    }
    /**
     * Returns the list of pictures that are stored in the bucket.
     *
     * @return array
     */
    public function listPictures()
    {
        return $this->_pictureList;
    }
    /**
     * Returns the latest picture (name, URL) that had been stored in the bucket.
     *
     * @return array
     */
    public function getLatestPicture()
    {
        if ($this->_pictureList === false || empty($this->_pictureList)) {
            return false;        
        } else {
            $latestPicture   = current($this->_pictureList);
            $picture['url']  = STORAGE_RESOURCE_URL . '/' . $latestPicture['name'];
            $picture['name'] = $latestPicture['name'];
            return $picture;
        }
    
    }
    /**
     * Returns the picture (name, URL) as specified by $pictureName.
     *
     * @param string $pictureName
     *
     * @return string
     */
    public function getPicture($pictureName)
    {
        $picture['url'] = STORAGE_RESOURCE_URL . '/' . $pictureName;
        $picture['name'] = $pictureName;
        return $picture;
    }
    /**
     * Deletes the specified picture ($pictureName) from the bucket.
     * Returns a message indicating if deletion is successful or not.
     *
     * @param string $pictureName
     *
     * @return string
     */
    public function deletePicture($pictureName)
    {
        if ($this->_storage->deleteObject(BUCKET, $pictureName)) {
            if ($this->_storage->deleteObject(COLOR_GRID_BUCKET, $pictureName)) {
                $this->_refreshList();
                return 'Picture deleted successfully';            
            }
        }
        return 'Picture or grid file not deleted successfully';
    }
    /**
     * Stores an uploaded picture in the bucket
     * Returns a message indicating if storage is successful or not.
     *
     * @param string $pictureName
     * @param string $pictureFile
     * @param string $pictureType
     * @param int    $pictureSize
     *
     * @return string
     */
    public function putPicture($pictureName, $pictureFile, $pictureType, $pictureSize)
    {
        $derivedType = strtolower(strrchr($pictureName, '.'));
        if (!$this->filterPictureName($pictureName)) {
            return 'Picture filename not valid. (, ) and | are not allowed.';
        } else if (!file_exists($pictureFile) || !is_file($pictureFile)) {
            return 'Picture not found';
        } else if (($pictureType !== 'image/jpeg') && ($pictureType !== 'image/png')) {
            return 'Only JPEG and PNG allowed';
        } else if (($derivedType !== '.jpg' ) && ($derivedType !== '.jpeg' )
            && ($derivedType !== '.png' )) {
            return 'Only JPEG and PNG allowed. Content-type tampering detected.';
        } else if ($pictureSize > MAX_SIZE) {
            return 'Maximum file size: 500 kb';
        } else if (sizeof($this->_pictureList) < 50) {      
            if ($this->_storage->putObjectFile($pictureFile, BUCKET, $pictureName,
                S3::ACL_PUBLIC_READ)) {
                if (COLOR_GRID) {
                    if ($this->_putColorGrid($pictureName, $pictureFile)) {
                        $this->_refreshList();
                        return 'Picture stored successfully.';
                    } else {
                        return 'Problem occured in creating color grid.';
                    }
                } else {
                    $this->_refreshList();
                    return 'Picture stored successfully.';
                }
            } else {
                return 'Could not place picture on cloud storage.';                
            }
        } else {
            return 'Picture not stored. Maximum amount of pictures reached.';
        }
    }
    /**
     * Creates color grid of given picture and store it on the cloud
     * Returns a bool indicating if creation and storage is succLessful or not.
     *
     * @param string $pictureName
     * @param string $pictureFile
     *
     * @return bool
     */
    protected function _putColorGrid($pictureName, $pictureFile)
    {
        if (!extension_loaded('gearman')) {
            try {
                $fgm              = new Gmagick_Fuzzy($pictureFile);
                $colorGrid        = $fgm->getFuzzyColorGrid(CELL_SIZE);
                $pictureColorGrid = '';
                foreach ($colorGrid as $rowKey => $colorRow) {
                    foreach ($colorRow as $colKey => $colorColumn) {
                        $pictureColorGrid .= $pictureName.'['.$rowKey.','.$colKey.
                        ']'.chr(9).substr($colorColumn, 3).PHP_EOL;
                    }
                }
                if ($this->_storage->putObject($pictureColorGrid, COLOR_GRID_BUCKET, $pictureName,
                    S3::ACL_PUBLIC_READ, array(), 'text/plain')) {
                    return true;              
                } else {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        } else {
            $this->_workerManager->adjustWorkers("putColorGridDelegated");
            $jobStatus = $this->_gearmanClient->doBackground("putColorGridDelegated", $pictureName);
            return ($this->_gearmanClient->returnCode() === GEARMAN_SUCCESS) ? TRUE : FALSE;    
        }
    }
    /**
     * Returns the picture (name, URL) as specified by $pictureSearchResult.
     *
     * @param string $pictureSearchResult
     *
     * @return string
     */
    public function getSearchMatches($pictureSearchResult)
    {
        $positions       = unserialize($this->_localStore->get($pictureSearchResult));
        $positionOverlay = new GMagickDraw();
        $positionOverlay->setFillColor('transparent')->setStrokeColor('yellow')
                        ->setStrokeWidth('2');
        foreach ($positions as $position => $color) {
            list($x, $y) = explode(',', rtrim(ltrim($position, '['), ']'));
            $positionOverlay->rectangle($x, $y, $x+CELL_SIZE, $y+CELL_SIZE);
        }
        list($fileName, $searchTime) = explode('|', $pictureSearchResult);
        $gmPicture       = new Gmagick(STORAGE_RESOURCE_URL.'/'.$fileName);
        $gmPicture->drawImage($positionOverlay);
        $gmPicture->write('tmp_img/'.$pictureSearchResult); // Ensure write permissions
        $picture['url']  = './tmp_img/'.$pictureSearchResult;
        $picture['name'] = $pictureSearchResult;
        return $picture;
    }
    /**
     * Obtains the list of pictures that is in the bucket and sort it by time.
     */
    protected function _refreshList()
    {
        try {
            $this->_pictureList = $this->_storage->getBucket(BUCKET, null, null, MAX_PICTURES);
        } catch (Exception $e) {
            echo "No Connection".$e->getMessage();
        }
        if ($this->_pictureList !== false) { 
            usort($this->_pictureList, array('PictureManager', 'pictureCompare'));
        }	
    }
    /**
     * Compares pictures by the time it is stored in the bucket.
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    static function pictureCompare($a, $b)
    {
        if ($a['time'] == $b['time']) {
            return 0;
        }
        return ($a['time'] > $b['time']) ? -1 : +1;
    }
    /**
     * Filters invalid picture name. We will not allow (, ) and | within the
     * file name of a picture to prevent conflict with our color grid indexing
     * syntax. 
     *
     * @param string $pictureName
     *     
     * @return bool
     */
    public function filterPictureName($pictureName)
    {
        foreach (array('(',')','|') as $disallowed) {
            if (strpos($pictureName, $disallowed) !== false) {
                return false;
            }
        }
        return true;
    }
    /**
     * Check that authorisation to access Picasa is available, obtains the 
     * necessary access token and use that to fetch information of the latest
     * picture uploaded to Picasa for the given $username.
     *
     * @return stdObj
     */  
    public function getLatestPicasaPicture($username)
    {
        $access = Manager::getInstance('UserManager')->checkAuthorization()->getAccess();
        if ($access->fetch("http://picasaweb.google.com/data/feed/api/user/{$username}?alt=json&kind=photo&max-results=1")) {
            return json_decode($access->getLastResponse());
        } else {
            throw new Exception("Problem fetching from Picasa");
        }
    }
    /**
     * Converts GPS location in degree form to decimal form.
     *
     * @param string $ref
     * @param array $line
     *
     * @return float
     */ 
    function gpsDegreeToDecimal($ref,$line)
    {
        switch ($ref) {
            case 'N':
            case 'E':
                return ($line[0]+((($line[1]*60)+($line['2']))/3600));
            case 'S':
            case 'W':
                return -($line[0]+((($line[1]*60)+($line['2']))/3600));
        }

    }
    /**
     * Converts fraction form to decimal form.
     *
     * @param array $fraction
     *
     * @return float
     */ 
    public function gpsFractionToDecimal($fraction)
    {
        list($num, $den) = explode('/', $fraction);
        return $num/$den;        
    }
    /**
     * Returns formatted Latitude and Longitude (decimal) given $exif header array.
     *
     * @param array $exif
     *
     * @return array
     */
    public function getLocation($exif)
    {
        foreach (array('GPSLatitude', 'GPSLongitude') as $line) {
            foreach ($exif["{$line}"] as $key => $value) {
                $exif["{$line}"]["{$key}"] = $this->gpsFractionToDecimal($value);
            }
        }

        $intLatitude = $this->gpsDegreeToDecimal($exif['GPSLatitudeRef'], $exif['GPSLatitude']);
        $intLongitude = $this->gpsDegreeToDecimal($exif['GPSLongitudeRef'], $exif['GPSLongitude']);

        return array('Latitude' => $intLatitude, 'Longitude' => $intLongitude);
    }
    /**
     * Returns required HTML (and Javascript) to display visual map with 
     * given $location
     *
     * @param array $location
     *
     * @return string
     */
    public function showLocation($location)
    {
        $location['Longitude'] = -0.23029446601867676;
        $location['Latitude'] = 51.589109550398;

        $this->_map->addMarkerByCoords($location['Longitude'],$location['Latitude'],NULL,$this->getCurrentPictureName());        
        return $this->_map->getMap();
    }
    /**
     * Returns name of current picture associated to the PictureManager instance.
     *
     * @return string
     */
    public function getCurrentPictureName()
    {
        return $this->_picture['name'];
    }
    /**
     * Returns scripts that may need to be included within the <head> of the view
     *
     * @return mixed
     */
    public function headScriptHelper()
    {
        if (!empty($this->_exif['GPSVersion'])) {
            $header = $this->_map->getHeaderJS(); 
            $map = $this->_map->getMapJS();
            return $header.$map;
        } else {
            return NULL;
        }
    }
    /**
     * Returns any attribute that may need to be included within the <body> tag
     *
     * @return mixed
     */
    public function bodyAttributeHelper()
    {
        return !empty($this->_exif['GPSVersion']) ? "onload='onLoad()'" : NULL;
    }
    /**
     * Manages requests submitted to the index view.
     * 
     * @param array $request
     *
     * @return array
     */
    public function manageRequest($request)
    {
        $picture = array('message' => NULL, 'src' => NULL, 'location' => NULL);

        if (isset($request['submit'])) {
            $picture['message'] .= $this->putPicture($_FILES['picture']['name'], 
                $_FILES['picture']['tmp_name'], $_FILES['picture']['type'], $_FILES['picture']['size']);
        } else if (isset($request['delete'])) {
            $picture['message'] .=  $this->deletePicture($_GET['delete']);
        }
        if (isset($request['pictureName'])) {
            $this->_picture = $this->getPicture($request['pictureName']);
        } else if (isset($request['showSearchMatches'])) {
            $this->_picture = $this->getSearchMatches($_GET['showSearchMatches']);
        } else {
            $this->_picture = $this->getLatestPicture();
        }
        if ($this->_picture === false) {
            $picture['message'] .=  'No photo to show';
        } else {
            $picture['src'] = $this->_picture['url'];
            $derivedType = strtolower(strrchr($this->_picture['url'], '.'));
            if ($derivedType === '.jpg' || $derivedType === '.jpeg') {
                $this->_exif = exif_read_data($this->_picture['url']);
                if (!empty($this->_exif['GPSVersion'])) {
                    $picture['location'] = $this->showLocation($this->getLocation($this->_exif));
                }
            }
        }
        return $picture;
    }

}
