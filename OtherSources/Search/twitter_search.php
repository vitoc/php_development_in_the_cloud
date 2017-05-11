<?php

$searchString = urlencode('#catan');
$format       = 'json'; // json or atom

$url = "http://search.twitter.com/search.{$format}?q={$searchString}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$json_result = curl_exec($ch);
curl_close($ch);

$result = json_decode($json_result);

print_r($result);
