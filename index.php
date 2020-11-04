<?php

require(__DIR__ . '/vendor/autoload.php');

use Classes\FileDataHandler;
use Classes\StatFromFileStorage;

$path = "access_log.txt";

$statFromFileStorage = new StatFromFileStorage();
$fileDataHandler = new FileDataHandler($path, $statFromFileStorage);

$fileDataHandler->getFileInfo();

$fileDataHandler->selectStatInfo();

//Вывод можно оформить в любом виде, это пример
echo 'Запросы обработывались со следующими статусами<br>';
foreach ($statFromFileStorage->get('statusCodes') as $key => $value) {
    echo $key . ' - ' . $value;
    echo '<br>';
}

echo "<br>Трафик - " . $statFromFileStorage->get('totalTraffic') . "<br>";

echo "<br>Уникальные адреса - ";
echo $statFromFileStorage->get('uniqueUrlsCount');
echo "<br>";
echo '<br>Поисковые роботы:<br>';
foreach ($statFromFileStorage->get('crawlers') as $key => $value) {
    echo $key . ' - ' . $value;
    echo '<br>';
}




