<?php

require(__DIR__ . '/vendor/autoload.php');

use Classes\FileDataHandler;
use Classes\StatFromFileStorage;

$path = "access_log.txt";

$statFromFileStorage = new StatFromFileStorage();
$fileDataHandler = new FileDataHandler($path, $statFromFileStorage);

$fileDataHandler->selectStatInfoEconomy();

echo $statFromFileStorage->getJson();




