<?php

/**
 * Helper and debug functions.
 *
 * @file    Helper.php
 * @author  Tomas <studnasoft@gmail.com>
 * @license https://github.com/tomascc MIT
 */

declare(strict_types=1);

namespace App\Helpers;

use App\Config\MyConfig;
use RuntimeException;

readonly class Helper
{


    public function __construct(private MyConfig $myConfig)
    {

    }//end __construct()


    /**
     * Extracts the target host from a proxy-formatted host.
     *
     * @param string $host The original host in proxy format.
     *
     * @return string The extracted remote host.
     * @throws RuntimeException If the host format is invalid.
     */
    public function extractTargetHost(string $host): string
    {
        $pattern = '/^(?<host>.+?)-(?<tld>.+?)\.proxy\.com$/';
        if (preg_match($pattern, $host, $matches)) {
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

        $pattern = '/<('.implode('|', $tags).')\s+[^>]*?(?:'.implode('|', $attributes).')="([^"]+)"/i';

        $callback = static function (array $matches) use ($proxyHost): string {
            $originalUrl = $matches[2];
            $parsedUrl   = parse_url($originalUrl);

            if (!isset($parsedUrl['host'])) {
                return $matches[0];
            }

            $proxySubdomain = str_replace('.', '-', $parsedUrl['host']).'-proxy.'.$proxyHost;
            $newUrl         = preg_replace('/^https?:\/\/[^\/]+/', 'https://'.$proxySubdomain, $originalUrl);

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
        if ($this->myConfig->debug === false && $force === false) {
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
        if ($this->myConfig->debug === false) {
            return;
        }

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head><title>'.htmlspecialchars($host, ENT_QUOTES, 'UTF-8').'</title></head>';
        echo '<body>';

    }//end debugStart()


    /**
     * Ends an HTML debug output.
     *
     * @return void
     */
    public function debugEnd(): void
    {
        if ($this->myConfig->debug === false) {
            return;
        }

        echo '</body>';
        echo '</html>';

    }//end debugEnd()


}//end class
