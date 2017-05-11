<?php
/**
 * Configuration file for PictureMe application.
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt 
 */
// Place actual values below
// Amazon AWS access key
define('ACCESS_KEY', '');
// Amazon AWS secret key
define('SECRET_KEY', '');
//The name of the bucket
define('BUCKET', '');
//The picture store URL in the form of http:// followed by the CloudFront domain name
//Has the form http://[account_name].blob.core.windows.net/[container_name] if using Azure
define('STORAGE_RESOURCE_URL', '');
//Restriction on the maximum amount of pictures that can be stored
define('MAX_PICTURES', 50);
//Restriction on the maximum size of each picture
define('MAX_SIZE', 5120000);
//Color grid cell size
define('CELL_SIZE', 10);
//Name of bucket that stores color grids (cannot be similar as picture bucket)
define('COLOR_GRID_BUCKET', '');
//Name of bucket that stores color index (cannot be similar as picture bucket)
define('COLOR_INDEX_BUCKET', '');
//Timezone
define('TIMEZONE', 'Europe/London');
//Tells Zend_OpenId to use sessions without being wrapped by Zend_Session
define('SID', true);
//The location of the file that specifies allowed OpenIDs
define('PM_USERS', '');
//Trigger color gridding on (TRUE) or off (FALSE)
define('COLOR_GRID', FALSE);
//Google Oauth consumer key
define('OAUTH_CONSUMER_KEY', '');
//Google Oauth consumer key
define('OAUTH_CONSUMER_SECRET', '');
//Google Maps API Key
define('GOOGLE_MAPS_API_KEY', '');
//Payflowpro URL
define('PAYFLOWPRO_URL', 'https://pilot-payflowpro.verisign.com/transaction');
//Payflowpro User
define('PAYFLOWPRO_USER', '');
//Payflowpro Vendor
define('PAYFLOWPRO_VENDOR', '');
//Payflowpro Partner
define('PAYFLOWPRO_PARTNER', 'paypal');
//Payflowpro Password
define('PAYFLOWPRO_PASSWORD', '');
//Annual subscription rate for PictureMe
define('ANNUAL_SUBSCRIPTION_RATE', '5');
// Specify either S3 or Azure. FALSE if you don't want to use SimpleCloud
define('SIMPLECLOUD', 'Azure');
//Azure Storage Host
define('AZURE_STORAGE_HOST', 'blob.core.windows.net');
//Azure Account Name
define('AZURE_ACCOUNT_NAME', '');
//Azure Account Key
define('AZURE_ACCOUNT_KEY', '');
//Azure Container
define('AZURE_CONTAINER', '');

function __autoload($className)
{
    $filePath     = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    $includePaths = explode(PATH_SEPARATOR, get_include_path());
    foreach ($includePaths as $includePath) {
        if (file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)) {
            require_once $filePath;
            return;
        }
    }
}
