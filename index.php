<?php

require_once 'Restable/Restable.php';
$app = new Restable();

$app->get('/', function() {
    echo 'It works!';
});

$app->start();
