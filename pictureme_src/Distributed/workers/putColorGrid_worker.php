<?php
require_once 'worker_config.inc.php';
/**
 * PutColorGrid Worker
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt  
 */
$storage = new S3(ACCESS_KEY, SECRET_KEY, false);
//microtime() can use gethostname upon 5.3
$workerReference = urlencode(gethostname().getmypid());

notifyManager('idle');
$worker= new GearmanWorker();
$worker->addServer(JOB_SERVER_IP);
$worker->addFunction('putColorGridDelegated', 'putColorGrid');
$worker->addFunction($workerReference, "kamikaze");

while ($worker->work());
function putColorGrid($job)
{
    try {
        global $storage;    
        notifyManager('work');
        //$pictureFile = $job->workLoad();
        $fgm              = new Gmagick_Fuzzy($pictureFile);
        $colorGrid        = $fgm->getFuzzyColorGrid(CELL_SIZE);
        $pictureColorGrid = '';
        foreach ($colorGrid as $rowKey => $colorRow) {
            foreach ($colorRow as $colKey => $colorColumn) {
                $pictureColorGrid .= $pictureName.'['.$rowKey.','.$colKey.
                ']'.chr(9).substr($colorColumn, 3).PHP_EOL;
            }
        }
        if ($storage->putObject($pictureColorGrid, COLOR_GRID_BUCKET, $pictureName,
            S3::ACL_PUBLIC_READ, array(), 'text/plain')) {
            notifyManager('idle');  
            return true;
        } else {
            notifyManager('error', $pictureFile);
            notifyManager('idle');
            return false;
        }
    } catch (Exception $e) {
        notifyManager('error', $pictureFile);
        return false;
    }
}

function notifyManager($status, $reference = NULL)
{
    global $workerReference;
    if (is_null($reference)) {
        $reference = $workerReference;
    }
    $ch = curl_init(WORKER_SERVER_SERVICE.'?'.$status.'='.$reference);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERPWD, WORKER_SERVER_USERNAME.':'.WORKER_SERVER_PASSWORD);
    curl_exec($ch);
    curl_close($ch);
}

function kamikaze($job)
{
    notifyManager('killed');
    echo "hhaiiiiiyakk!";
    exit;
}
