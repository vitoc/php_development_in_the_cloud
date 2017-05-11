<?php
require_once 'WorkerManager.php';
/**
 * Process that checks for idle workers at the pre-defined WORKER_MONITOR_FREQUENCY
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt 
 */
$workerManager = Distributed_WorkerManager::getInstance();
while(TRUE) {
    sleep(WORKER_MONITOR_FREQUENCY);
    try {
        echo "Checking workers...".PHP_EOL;
        $workerManager->checkWorkers();
    } catch (Exception $e) {
        error_log("Exception caught checking workers: ".$e->getMessage());
    }
}
