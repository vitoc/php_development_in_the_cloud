#!/usr/bin/php
<?php

while (($line = fgets(STDIN)) !== false) {
    $line = trim($line);
    list($color, $locations) = explode(chr(9), $line);
    if (empty($wholeColorArray["{$color}"])) {
        $wholeColorArray["{$color}"] = $locations;
    } else {
        $wholeColorArray["{$color}"] = implode('|', array($wholeColorArray["{$color}"], $locations));
    }
}

foreach($wholeColorArray as $color => $locations) {
    echo $color, chr(9), $locations.PHP_EOL;
}

?>
