<?php

if(isset($_SERVER['HTTP_X_FUCK']) && $_SERVER['HTTP_X_FUCK'] === 'yeah'){
    define('DEBUG', true);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    define('DEBUG', false);
}

include_once("../src/app.php");