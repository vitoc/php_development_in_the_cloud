<?php
require_once 'worker_manager_config.inc.php';
/**
 * Manages workers and worker servers
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
class Distributed_WorkerManager
{
    /**
     * The object's singular instance.
     *
     * @var object
     */
    protected static $_instance = null;
    /**
     * Rackspace cloud service
     *
     * @var RackCloudService
     */
    protected $_service = false;
    /**
     * Job server connection
     *
     * @var 
     */
    protected $_jobServer;
    /**
     * Authentication for Rackspace services
     *
     * @var RackAuth
     */
    protected $_authentication;
    /**
     * The TokyoTyrant store object
     *
     * @var TokyoTyrant
     */
    protected $_localStore;
    /**
     * The Gearman client 
     *
     * @var GearmanClient
     */        
    protected $_gearmanClient;   
     
    public function __construct()
    {
        $this->_jobServer = fsockopen('localhost', 4730);
        $this->_authentication = new RackAuth(RACKSPACE_USERNAME, RACKSPACE_API_KEY);
        $this->_authentication->auth();
        $this->_service = new RackCloudService($this->_authentication);
        if (extension_loaded('tokyo_tyrant')) {
            $this->_localStore = new TokyoTyrant('localhost', 1978);
        } else {
            throw new Exception('TokyoTyrant extension is required.');
        }
        if (extension_loaded('gearman')) {
            $this->_gearmanClient = new GearmanClient();
            $this->_gearmanClient->addServer();
        } else {
            throw new Exception('Gearman extension is required.');
        }      
    }
    /**
     * Gets an instance of the WorkerManager.
     *
     * @param int $manager
     *     
     * @return WorkerManager singleton instance of manager
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Distributed_WorkerManager;
        }
        return self::$_instance;
    }    
    /**
     * Obtain the latest list of all worker servers spawned
     *
     * @return array
     */
    public function serverUpdates()
    {
        return $this->_service->listServers();
    }
    /**
     * Shut down the server with the given serverName
     *
     * @param string $serverName
     *
     */
    public function killServer($serverName)
    {
        if ($this->killAllServerWorkers($serverName)) {
            $server = unserialize($this->_localStore->get($serverName));
            $response = $this->_service->deleteServer($server->server->id, TRUE);
            if (!is_null($server->cloudServersFault)) {
                throw new Exception('Unable to kill worker server.');        
            }
        }
    }
    /**
     * Create a new worker server and register it in local store
     *
     */
    public function spawnServer()
    {
        $latestServerIndex = $this->_localStore->get('latestServerIndex') ? $this->_localStore->get('latestServerIndex') : 0;
        $latestServerIndex++;
        $workerName = 'Worker'.$latestServerIndex;
        $server = $this->_service->createServer($workerName, WORKER_SERVER_IMAGE_ID, 1, NULL);
        if (is_null($server->cloudServersFault)) {
            $this->_localStore->put($workerName, serialize($server));
            $this->_localStore->put('latestServerIndex', $latestServerIndex);        
        } else {
            throw new Exception('Unable to spawn new worker server.');
        }
    }
    /**
     * Stops the worker process with the specified $workerName
     *
     * @param string $workerName
     */
    public function killWorker($workerName)
    {
        $workerReference = substr($workerName, 3);
        $this->_gearmanClient->doBackground($workerReference, '');
        $this->_localStore->out($workerName);
        if ($this->_gearmanClient->returnCode() !== GEARMAN_SUCCESS) {
            throw new Exception('Could not issue worker termination job.');
        }      
    }
    /**
     * Stops all worker processes running on the server with indicated $serverName
     * 
     * @param string $workerName
     */
    public function killAllServerWorkers($serverName)
    {
        try {
            $workerNames = $this->_localStore->fwmKeys("WRK".$serverName, WORKER_LIMITS);
            foreach ($workerNames as $workerName) {
                $this->killWorker($workerName);
                while ($this->_localStore->get($workerName) !== 'KILLED');
                $this->_localStore->out($workerName);            
            }
            return TRUE;
        } catch (Exception $e) {
            throw new Exception("Exception caught killing all workers");
        }
    }    
    /**
     * Create a new worker process on the latest available server
     *
     */
    public function spawnWorker()
    {
        if ($this->_localStore->get('latestServerIndex') === NULL) {
            $latestServer = 'localhost';
            $serverPassword = WORKER_SERVER_PASSWORD;
        } else {
            $latestServerName = 'Worker'.$this->_localStore->get('latestServerIndex');
            $latestServerObject = unserialize($this->_localStore->get($latestServerName));
            $latestServer = $latestServerObject->server->addresses->public[0];
            $serverPassword = $latestServerObject->server->adminPass;
        }
        $conn = ssh2_connect($latestServer, 22);
        if (!($conn) || !(ssh2_auth_password($conn, WORKER_SERVER_USERNAME, $serverPassword)) || 
        !($stream = ssh2_exec($conn, SPAWN_WORKER_COMMAND))
        ) {
            throw new Exception('Worker spawn failed.');
        } else {
            /*
            stream_set_blocking( $stream, true );
            $data = "";
            while( $buf = fread($stream,4096) ){
                $data .= $buf;
            }
            fclose($stream);
            print_r($data);
            */
        }
    }
    /**
     * Adjust the number of workers spawned. Will spawn new workers if there are 
     * jobs in the queue or no capable workers.
     *
     * @param string $function
     */   
    public function adjustWorkers($function)
    {    
        $status = $this->getJobServerStatus();
        if ((sizeof($status) === 0) ||($status["{$function}"]['in_queue'] > 0) || ($status["{$function}"]['capable_workers'] == 0)) {
            $this->spawnWorker();
        }
    }
    /**
     * Indicate that a worker with the given $workerReference is idle or working
     *
     * @param string $workerReference
     */
    public function workerStatus($status, $workerReference)
    {
        $this->_localStore->put('WRK'.$workerReference, $status.'|'.time());
    }
    /**
     * Check and kill workers that had been idling for a period that is longer than
     * the allowed idle duration.
     *
     */
    public function checkWorkers()
    {
        $workers = $this->_localStore->get($this->_localStore->fwmKeys("WRK", WORKER_LIMITS));
        foreach ($workers as $workerName => $status) {
            list($activity, $since) = explode('|', $status);
            if (($activity === 'IDLE') && ((time() - $since) > IDLE_ALLOWANCE)) {
                $this->killWorker($workerName);
            } else if (($activity === 'KILLED')) {
                $this->_localStore->out($workerName);            
            }        
        }
    }
    /**
     * Check and kill workers that had been idling for a period that is longer than
     * the allowed idle duration.
     *
     */    
    public function getJobServerStatus()
    {   
        $response = $this->_jobServerCommand('status');
        $functionStatus = array();
        $functions = explode("\n", $response);
        foreach ($functions as $function) {
            if (!strlen($function)) {
                continue;
            }
            list($functionName, $inQueue, $jobsRunning, $capable) = explode("\t", $function);
            $functionStatus["{$functionName}"] = array(
                'in_queue' => $inQueue,
                'jobs_running' => $jobsRunning,
                'capable_workers' => $capable
            );
        }
        return $functionStatus;        
    }
    /**
     * Delete worker server image with the specified $imageId
     *
     * @param string $imageId
     *
     * @return stdClass
     */
    public function deleteWorkerServerImage($imageId)
    {
        $image = new RackCloudImage($this->_authentication);   
        return $image->deleteImage($imageId);
    }
    /**
     * Delete worker server image with the specified $imageId
     *
     * @param string $imageId
     *
     * @return stdClass
     */
    public function createWorkerServerImage($serverId)
    {
        $image = new RackCloudImage($this->_authentication);    
        return $image->createImage($serverId, 'WorkerServer'); 
    }
    /**
     * Issue a command to the job server and returns the result of the command
     *
     * @param string $command
     *
     * @return string
     */    
    protected function _jobServerCommand($command)
    {
        fwrite($this->_jobServer, "{$command}\r\n", strlen("{$command}\r\n"));
        $ret = '';
        while (true) {
            $data = fgets($this->_jobServer, 4096);
//            $data = trim($data);
//            if (preg_match('/^ERR/', $data)) {
//                list(, $code, $msg) = explode(' ', $data);
//                throw Exception($msg, urldecode($code));
//            }
            if ($data == ".\n") {
                break;
            }
            $ret .= $data;
        }
        return $ret;
    }
}
