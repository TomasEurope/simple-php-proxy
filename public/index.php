<?php

/*
 * Initializes the application by setting configurations and starting the main app logic.
 *
 * - Autoloads necessary dependencies using Composer's autoload file.
 * - Conditionally sets error reporting based on the presence and value of a specific HTTP header.
 * - Instantiates the configuration object and initializes application configuration.
 * - Creates a new application instance and starts the application.
 *
 * @file index.php
 *
 * @author  Tomas <studnasoft@gmail.com>
 * @license https://github.com/tomascc MIT
 */

declare(strict_types=1);

namespace App;

use Exception;

// Autoload dependencies (Slim, Guzzle, and other packages).
/**
 * @psalm-suppress MissingFile
 */
require __DIR__ . '/../vendor/autoload.php';

if (isset($_SERVER['HTTP_X_FUCK']) === true && $_SERVER['HTTP_X_FUCK'] === 'yeah') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

try {
    $app = new App((new Helper((new Config())->initialize())));
    $app->start();
} catch (Exception $e) {
    echo "<pre>" . print_r($e, true) . "</pre>";
}
