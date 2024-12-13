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

use Exception;
use RuntimeException;

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
        $pattern = '/^(?<host>.+)\.[a-z]+\.[a-z]+$/';

        // Match the host against the proxy format pattern.
        if ((bool) preg_match($pattern, $host, $matches) === true) {
            // Combine matched groups to form the original host.
            return str_replace('xyx', '.', $matches['host']);
        }

        // Throw an exception if the host doesn't match the expected format.
        throw new RuntimeException('Invalid host format - ' . $host);
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
        $attributes = ['href', 'src', 'action'];
        $tags = ['a', 'img', 'script', 'link', 'form'];

        // Regex pattern to find specified attributes in specific tags.
        $pattern = '/<(' . implode('|', $tags) . ')\b[^>]*\b(' . implode('|', $attributes) . ')="([^"]+)"/i';

        $callback = static function (array $matches) use ($proxyHost): string {
            $originalUrl = $matches[3];

            // Parse and process the URL
            if (strpos($originalUrl, '//') === 0) {
                // URLs starting with //
                $parsedUrl = parse_url('https:' . $originalUrl);
            } elseif (parse_url($originalUrl, PHP_URL_SCHEME) === null) {
                // Relative URLs - leave them unchanged
                return $matches[0];
            } else {
                $parsedUrl = parse_url($originalUrl);
            }

            // Check if the URL is already rewritten to the proxy
            if (isset($parsedUrl['host']) && str_ends_with($parsedUrl['host'], $proxyHost)) {
                // If already rewritten, return the original tag without modification
                return $matches[0];
            }

            // Rewrite only if the URL has a host
            if (isset($parsedUrl['host'])) {
                $proxySubdomain = str_replace('.', 'xyx', $parsedUrl['host']) . '.' . $proxyHost;

                $newUrl = $parsedUrl['scheme'] . '://' . $proxySubdomain;

                if (isset($parsedUrl['path'])) {
                    $newUrl .= $parsedUrl['path'];
                }
                if (isset($parsedUrl['query'])) {
                    $newUrl .= '?' . $parsedUrl['query'];
                }
                if (isset($parsedUrl['fragment'])) {
                    $newUrl .= '#' . $parsedUrl['fragment'];
                }

                // Return the updated tag with the rewritten URL
                return str_replace($originalUrl, $newUrl, $matches[0]);
            }

            // Return unchanged tag if no host is found
            return $matches[0];
        };

        // Apply the callback to replace matching patterns in the HTML.
        $result = preg_replace_callback($pattern, $callback, $html);

        if ($result === null) {
            throw new RuntimeException('Regex error or invalid input.');
        }
        return $result;
    }


    /**
     * Insert our JavaScript to replace URLs on the fly
     *
     * @param string $html
     *
     * @return string
     */
    public function insertScript(string $html): string
    {
        return (string) preg_replace(
            '/\<body[^>]*\>/',
            '<body><script src="https://' . $this->config->proxyHost . '/script.js"></script>',
            $html
        );
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
        if ($this->config->debug === false) {
            return;
        }

        if (empty($host) === true) {
            throw new RuntimeException('Undefined host');
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
        if ($this->config->debug === false) {
            return;
        }

        echo '</body></html>';
    }

    /**
     * Logs exception details to a specified file.
     *
     * @param Exception $e The exception to be logged.
     * @return void
     */
    public function log(Exception $e): void
    {
        $line = date('d.m.Y H:i:s') . ' - ' . $e->getCode() . ' - ' . explode(" response", $e->getMessage())[0];
        file_put_contents($this->config->logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

}
