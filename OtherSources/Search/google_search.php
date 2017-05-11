<?php

$searchString = rawurlencode('The Count of Monte Cristo');
$key          = ''; //Optional
$userIp       = ''; //Optional

$url  = "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q={$searchString}";
$url .= !empty($key) ? "&key={$key}" : '';
$url .= !empty($key) ? "&userip={$userIp}" : '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$json_result = curl_exec($ch);
curl_close($ch);

$result = json_decode($json_result);
print_r($result);
