<?php
require "vendor/autoload.php";
use Dotenv\Dotenv;

Use Tasker\Api;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$rest = new API();

$rest->request();
$rest->process();
$rest->response();

?>
