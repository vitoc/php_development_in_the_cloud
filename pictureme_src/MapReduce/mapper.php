#!/usr/bin/php
<?php

while (($line = fgets(STDIN)) !== false) {
    $line = trim($line);
    list($location,$color) = explode(chr(9), $line);
    if (empty($colorArray["{$color}"])) {
        $colorArray["{$color}"] = $location;
    } else {
        $colorArray["{$color}"] = implode('|', array($colorArray["{$color}"], $location));
    }
}

foreach($colorArray as $color => $locations) {
    echo $color, chr(9), $locations.PHP_EOL;
}

?>
