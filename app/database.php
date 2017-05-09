<?php

$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'nlpe',
    'username' => 'root',
    'password' => 'newbo-ok',
    'charset' => 'utf8',
    'collation' => 'utf8_general_ci',
    'prefix' => ''
]);
$capsule->setEventDispatcher(new Illuminate\Events\Dispatcher());
$capsule->setAsGlobal();
$capsule->bootEloquent();
date_default_timezone_set('America/Argentina/Buenos_Aires');
