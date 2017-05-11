<?php
require_once 'WorkerManager.php';
/**
 * Service that listens to worker and worker server status-es and requests
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt 
 */
$workerServerManager = Distributed_WorkerManager::getInstance();
try {
    if (isset($_GET['spawn'])) {
        $workerServerManager->spawnServer();
    } else if (isset($_GET['kill'])) {
        $workerServerManager->killServer($_GET['kill']);
    } else if (isset($_GET['idle'])) {
        $workerServerManager->workerStatus('IDLE', $_GET['idle']);
    } else if (isset($_GET['work'])) {
        $workerServerManager->workerStatus('WORK', $_GET['work']);
    } else if (isset($_GET['killed'])) {
        $workerServerManager->workerStatus('KILLED', $_GET['killed']);
    } else if (isset($_GET['error'])) {
        error_log('Message: Job Error. Time: '.time().'. File: '.$_GET['error']);
    }
} catch (Exception $e) {
    error_log('Message: '.$e->getMessage().'Time: '.time());
}
