<?php

namespace App;

use App\Controllers\App;
use App\Config\MyConfig;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Autoload dependencies (Slim, Guzzle, and other packages)
require '../vendor/autoload.php';

/*
if(isset($_SERVER['HTTP_X_FUCK']) && $_SERVER['HTTP_X_FUCK'] === 'yeah'){
    define('DEBUG', true);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    define('DEBUG', false);
}
*/


$config = new MyConfig();
$config->initialize();

$app = new App((new Helpers\Helper($config)));
$app->start();
