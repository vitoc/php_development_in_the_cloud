<?php

import access.AccessManager;
$accessManager = new AccessManager();
$hostname  = $_SERVER[''hostname]; //gethostname() available in PHP 5.3 is not supported in Quercus
$access    = $accessManager->getAccessInfo($hostname);
echo "<a href='{$access['url']}'>{$access['display']}</a>";
