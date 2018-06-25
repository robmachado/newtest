<?php
include 'vendor/autoload.php';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__));
}

use Dotenv\Dotenv;
$dotenv = new Dotenv(__DIR__);
$dotenv->load();


