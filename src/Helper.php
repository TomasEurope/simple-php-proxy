<?php

/**
 * This file contains the Helper class, which includes various utility methods
 * for modifying HTML content, extracting hosts from proxy formats, and managing
 * debug outputs. The class is meant to be used with a configuration object to
 * control its behavior, particularly in debug mode.
 *
 * @file Helper.php
 *
 * @author  Tomas <studnasoft@gmail.com>
 * @license https://github.com/tomascc MIT
 */

declare(strict_types = 1);

namespace App;

use RuntimeException;
use function htmlspecialchars;
use function implode;
use function parse_url;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function print_r;
use function str_replace;
use const ENT_QUOTES;

readonly final class Helper
{


    /**
     * Construct :)
     *
     * @param Config $config Configuration object.
     */
    public function __construct(public Config $config)
    {

    }//end __construct()


    /**
     * Extracts the target host from a proxy-formatted host.
     *
     * @param string $host The original host in proxy format.
     *
     * @return string The extracted remote host.
     *
     * @throws RuntimeException If the host format is invalid.
     */
    public function extractTargetHost(string $host): string
    {
        $pattern = '/^(?<host>.+?)-(?<tld>.+?)\.proxy\.com$/';

        if ((bool) preg_match($pattern, $host, $matches) === true) {
            return $matches['host'].'.'.$matches['tld'];
        }

        throw new RuntimeException('Invalid host format');

    }//end extractTargetHost()


    /**
     * Rewrites URLs in specific HTML attributes to route them through the proxy.
     *
     * @param string $html      The original HTML content.
     * @param string $proxyHost The proxy host to use (e.g., "proxy.com").
     *
     * @return string The modified HTML content with rewritten URLs.
     */
    public function replaceUrlsWithProxy(string $html, string $proxyHost): string
    {
        $attributes = [
            'href',
            'src',
            'action',
        ];
        $tags       = [
            'a',
            'img',
            'script',
            'link',
            'form',
        ];

        $pattern = '/<('.implode('|', $tags).')\s+[^>]*?(?:'.\implode('|', $attributes).')="([^"]+)"/i';

        $callback = static function (array $matches) use ($proxyHost): string {
            $originalUrl = $matches[2];
            if (isset($originalUrl) === false
                || is_string($originalUrl) === false
                || empty(trim($originalUrl)) === true
            ) {
                throw new RuntimeException;
            }

            $parsedUrl = parse_url($originalUrl);

            $proxySubdomain = str_replace('.', '-', $parsedUrl['host']).'.'.$proxyHost;
            $newUrl = preg_replace('/^https?:\/\/[^\/]+/', 'https://'.$proxySubdomain, $originalUrl);

            return str_replace($originalUrl, $newUrl, $matches[0]);
        };

        return preg_replace_callback($pattern, $callback, $html);

    }//end replaceUrlsWithProxy()


    /**
     * Outputs debugging information in HTML format.
     *
     * @param string $type    The type of the debug message.
     * @param mixed  $content The content to display.
     * @param string $color   The text color for the debug message.
     * @param bool   $force   Whether to force output regardless of DEBUG mode.
     *
     * @return void
     */
    public function d(
        string $type,
        mixed $content='',
        string $color='black',
        bool $force=false
    ): void {
        if ($this->config->debug === false && $force === false) {
            return;
        }

        echo "<hr><h3 style='color: {$color}'>{$type}</h3><pre>".print_r($content, true).'</pre><hr />';

    }//end d()


    /**
     * Starts an HTML debug output.
     *
     * @param string $host The host to display in the debug output.
     *
     * @return void
     */
    public function debugStart(string $host): void
    {
        if ($this->config->debug === true) {
            return;
        }

        echo '<!DOCTYPE html><html lang="en">';
        echo '<head><title>'.htmlspecialchars($host, ENT_QUOTES, 'UTF-8').'</title></head><body>';

    }//end debugStart()


    /**
     * Ends an HTML debug output.
     *
     * @return void
     */
    public function debugEnd(): void
    {
        if ($this->config->debug === true) {
            return;
        }

        echo '</body></html>';

    }//end debugEnd()


}//end class
