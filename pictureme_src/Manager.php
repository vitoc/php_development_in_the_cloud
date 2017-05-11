<?php
require_once 'config.inc.php';
/**
 * Manages searches within PictureMe S3 storage
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
class Manager
{
    /**
     * The object's singular instance.
     *
     * @var object
     */
    protected static $_instances = array();
    /**
     * The TokyoTyrant store object
     *
     * @var TokyoTyrant
     */
    protected $_localStore;
        
    public function __construct()
    {
        set_error_handler(get_class($this).'::manageErrors');
        date_default_timezone_set(TIMEZONE);         
        try {
            if (extension_loaded('tokyo_tyrant')) {
                $this->_localStore = new TokyoTyrant('localhost', 1978);
            }
        } catch (Exception $e) {
            trigger_error($e->getMessage().'. ', E_USER_WARNING);        
        }
    }
    /**
     * Gets an instance of the Manager.
     *
     * @param int $manager
     *     
     * @return Manager singleton instance of manager
     */
    public static function getInstance($manager)
    {
        if (!isset(self::$_instances["{$manager}"]) || is_null(self::$_instances["{$manager}"])) {
            self::$_instances["{$manager}"] = new $manager($manager);
        }
        return self::$_instances["{$manager}"];
    }
    /**
     * Manage any error triggered
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     */
    static function manageErrors($errno, $errstr, $errfile, $errline)
    {
        echo "Problem detected. Code: {$errno}. {$errstr}. ";
        error_log("Message: {$errstr}. File: {$errfile}. Line: {$errline}.");
    }
}
