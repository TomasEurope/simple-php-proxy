<?php

/**
 * Configuration
 *
 * @file    MyConfig.php
 * @author  Tomas <studnasoft@gmail.com>
 * @license https://github.com/tomascc MIT
 */

declare(strict_types=1);

namespace App\Config;

/**
 * Configuration class for global settings.
 */
class MyConfig
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

    }//end initialize()


}//end class
