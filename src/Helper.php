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

declare(strict_types=1);

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
    }

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

        // Match the host against the proxy format pattern.
        if ((bool) preg_match($pattern, $host, $matches) === true) {
            // Combine matched groups to form the original host.
            return $matches['host'] . '.' . $matches['tld'];
        }

        // Throw an exception if the host doesn't match the expected format.
        throw new RuntimeException('Invalid host format');
    }

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
        $tags = [
            'a',
            'img',
            'script',
            'link',
            'form',
        ];

        // Regex pattern to find specified attributes in specific tags.
        $pattern = '/<(' . implode('|', $tags) . ')\s+[^>]*?(?:' . implode('|', $attributes) . ')="([^"]+)"/i';

        $callback = static function (array $matches) use ($proxyHost): string {
            $originalUrl = $matches[2];

            // Validate and parse the original URL.
            if (
                isset($originalUrl) === false
                || is_string($originalUrl) === false
                || empty(trim($originalUrl)) === true
            ) {
                throw new RuntimeException();
            }

            $parsedUrl = parse_url($originalUrl);

            // Ensure the URL contains a valid host.
            if (isset($parsedUrl['host']) === false) {
                throw new RuntimeException();
            }

            // Construct a proxy subdomain.
            $proxySubdomain = str_replace('.', '-', $parsedUrl['host']) . '.' . $proxyHost;

            // Rewrite the URL to route through the proxy.
            $newUrl = preg_replace('/^https?:\/\/[^\/]+/', 'https://' . $proxySubdomain, $originalUrl);

            // Make sure we have only string types
            if (is_string($newUrl) === false || is_string($matches[0]) === false) {
                throw new RuntimeException();
            }

            // Ensure final URL replacement is valid and return the updated string.
            $finalUrl = str_replace($originalUrl, $newUrl, $matches[0]);
            if (empty($finalUrl)) {
                throw new RuntimeException();
            }
            return $finalUrl;
        };

        // Apply the callback to replace matching patterns in the HTML.
        $result = preg_replace_callback($pattern, $callback, $html);
        if (is_null($result) === true || empty($result) === true) {
            throw new RuntimeException();
        }
        return $result;
    }

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
        mixed $content = '',
        string $color = 'black',
        bool $force = false
    ): void {
        // Skip output if not in debug mode and force is false.
        if ($this->config->debug === false && $force === false) {
            return;
        }

        // Output debug message in a formatted HTML block.
        echo "<hr><h3 style='color: {$color}'>{$type}</h3><pre>" . print_r($content, true) . '</pre><hr />';
    }

    /**
     * Starts an HTML debug output.
     *
     * @param string $host The host to display in the debug output.
     *
     * @return void
     */
    public function debugStart(string $host): void
    {
        // Output the start of an HTML debug page if not in debug mode.
        if ($this->config->debug === true) {
            return;
        }

        if (empty($host) === true) {
            throw new RuntimeException();
        }

        echo '<!DOCTYPE html><html lang="en">';
        echo '<head><title>' . htmlspecialchars($host, ENT_QUOTES, 'UTF-8') . '</title></head><body>';
    }

    /**
     * Ends an HTML debug output.
     *
     * @return void
     */
    public function debugEnd(): void
    {
        // Output the end of an HTML debug page if not in debug mode.
        if ($this->config->debug === true) {
            return;
        }

        echo '</body></html>';
    }
}
