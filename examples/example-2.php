<?php

$name = "John Doe";
$parts = explode(" ", $name);
$newParts = [];

foreach ($parts as $part) {
    $newParts[] = strtoupper($part);
}

echo implode(" ", $newParts);
