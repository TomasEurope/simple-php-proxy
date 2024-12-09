<?php

/**
 * This file contains the configuration class for handling global settings
 * within the application. It deals with enabling or disabling debugging
 * based on specified server headers.
 *
 * @file Config.php
 *
 * @author  Tomas <studnasoft@gmail.com>
 * @license https://github.com/tomascc MIT
 */

declare(strict_types=1);

namespace App;

/**
 * Configuration class for global settings.
 */
final class Config
{
    /**
     * Whether debugging is enabled.
     *
     * @var boolean
     */
    public bool $debug = false;


    /**
     * Initialize the configuration settings.
     *
     * @return void
     */
    public function initialize(): void
    {
        // Set DEBUG based on header.
        if (isset($_SERVER['HTTP_X_FUCK']) === true && $_SERVER['HTTP_X_FUCK'] === 'yeah') {
            $this->debug = true;
        }
    }
}
