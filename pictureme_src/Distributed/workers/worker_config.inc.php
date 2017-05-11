<?php
/**
 * Configuration file for PictureMe application.
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt 
 */
require_once('S3.php');
require_once('Gmagick/Fuzzy.php');
// Place actual values below
// Amazon AWS access key
define('ACCESS_KEY', '');
// Amazon AWS secret key
define('SECRET_KEY', '');
//Color grid cell size
define('CELL_SIZE', 10);
//Name of bucket that stores color grids (cannot be similar as picture bucket)
define('COLOR_GRID_BUCKET', '');
//Name of bucket that stores color index (cannot be similar as picture bucket)
define('COLOR_INDEX_BUCKET', '');
//Timezone
define('TIMEZONE', 'Europe/London');
//IP address of server where job server resides
define('JOB_SERVER_IP', '');
//Worker Server Service Location
define('WORKER_SERVER_SERVICE', '');
//Worker Server Service Username
define('WORKER_SERVER_USERNAME', '');
//Worker Server Service Password
define('WORKER_SERVER_PASSWORD', '');

