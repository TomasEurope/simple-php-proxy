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

use RuntimeException;

use function preg_match;

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
     * Our proxy domain
     *
     * @var string
     */
    public string $proxyHost = '';


    /**
     * Initialize the configuration settings.
     *
     * @return self
     */
    public function initialize(): self
    {
        // Set DEBUG based on header.
        if (isset($_SERVER['HTTP_X_FUCK']) === true && $_SERVER['HTTP_X_FUCK'] === 'yeah') {
            $this->debug = true;
        }

        if (isset($_SERVER['HTTP_HOST']) === true) {
            /**
             * @psalm-suppress UnnecessaryVarAnnotation
             * @var string $host
             */
            $host = $_SERVER['HTTP_HOST'];
            if (preg_match('/([a-z]+\.[a-z]+)$/', $host, $matches) === false) {
                throw new RuntimeException('Can\'t exctract our proxy host from ' . $host);
            }
            if (count($matches) === 2 && empty($matches[1]) !== true) {
                $this->proxyHost = $matches[1];
            } else {
                throw new RuntimeException('Can\'t exctract our proxy host from ' . $host);
            }
        } else {
            throw new RuntimeException('Undefined HTTP_HOST - ' . $this->proxyHost);
        }

        return $this;
    }
}
