#!/bin/bash          
SERVERNAME=`hostname`
curl -u [USERNAME]:[PASSWORD] http://[PRIMARY_SERVER_URL]/pictureme/Distributed/worker_server_service.php?kill=$SERVERNAME
