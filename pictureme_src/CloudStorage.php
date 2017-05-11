<?php
/**
 * Adaptable storage that has an interface that is users of the S3 class will find familiar.
 * Methods are mostly similar but not entirely the same. See method comments for details.
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
class CloudStorage
{
    /**
     * Cloud storage adapter.
     *
     * @var array
     */
    protected $_adapter = NULL;
    public function __construct($provider)
    {
        $constructor = "construct{$provider}Storage";
        $this->$constructor();
    }
    /**
     * Constructs Azure storage adapter.
     */
    public function constructAzureStorage()
    {
        $azureOptions = array(
            'storage_adapter'     => 'Zend_Cloud_Storage_Adapter_WindowsAzure',
            'storage_host'        => AZURE_STORAGE_HOST, //Endpoint
            'storage_accountname' => AZURE_ACCOUNT_NAME,
            'storage_accountkey'  => AZURE_ACCOUNT_KEY,
            'storage_container'   => 'pictureme'// Bucket
        );
        $this->_adapter = Zend_Cloud_Storage_Factory::getAdapter($azureOptions);
    }
    /**
     * Returns a list of contents stored in the associated bucket (container).
     *
     * @return array
     */
    public function getBucket()
    {
        $items = array();
        foreach ($this->_adapter->listItems('', array('returntype' => 2)) as $item) {
            if ($item->name !== '/') {
                $items["{$item->name}"] = array(
                    'name' => $item->name,
                    'time' => strtotime($item->lastmodified),
                    'size' => $item->size,
                    'hash' => md5($item->name)
                );
            }
        }
        return $items;
    }
    /**
     * Put an object from a file
     *
     * @param string $file
     * @param string $bucket
     * @param string $uri 
     * @param constant $acl
     * @param string $type
     *
     * @return bool
     *
     */
    public function putObjectFile($file, $bucket, $pictureName, $acl, $type)
    {
        try {
            $this->_adapter->storeItem($file, $pictureName);
            return TRUE;
	} catch (Exception $e) {
            return FALSE;
        }
    }
    /**
     * Delete an object
     * @param string $bucket
     * @param string $uri
     *
     * @return bool
     */
    public function deleteObject($bucket, $uri)
    {
	try {
	    $this->_adapter->deleteItem($uri);
	    return TRUE;
	} catch (Exception $e) {
	    return FALSE;
	}
    }
}
