<?php
/**
 * Configuration file for WorkerManager.
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt 
 */
// Place actual values below
// Rackspace Username
define('RACKSPACE_USERNAME', '');
// Rackspace API Key
define('RACKSPACE_API_KEY', '');
// Worker Server Username
define('WORKER_SERVER_USERNAME', '');
// Worker Server Password
define('WORKER_SERVER_PASSWORD', '');
// Worker Server Password
define('WORKER_MONITOR_FREQUENCY', 60);
// Command to start a new worker process on the worker server
define('SPAWN_WORKER_COMMAND', 'php /var/www/cloud/pictureme_src/Distributed/workers/putColorGrid_worker.php &');
// Maximum cap on the amount of workers
define('WORKER_LIMITS', 100);
// Amount of time in seconds to allow workers to idle
define('IDLE_ALLOWANCE', 5);

require_once("phprackcloud/class.rackcloudmanager.php");
