<?php

function extractTargetDomain(string $host): string {
    $pattern = '/^(?<domain>.+?)-(?<tld>.+?)\.proxy\.com$/';
    if (preg_match($pattern, $host, $matches)) {
        return $matches['domain'] . '.' . $matches['tld'];
    }
    throw new \RuntimeException('Invalid host format');
}

/**
 * Replace URLs in specific HTML attributes to route them through the proxy.
 *
 * Handles attributes such as `href`, `src`, and `action` in tags like
 * <a>, <img>, <script>, <link>, and <form>.
 *
 * @param string $html       The original HTML content.
 * @param string $proxyHost  The proxy host to use (e.g., "proxy.com").
 * @return string            The modified HTML content with rewritten URLs.
 */
function replaceUrlsWithProxy(string $html, string $proxyHost): string {
    // Define the attributes and tags to process
    $attributes = ['href', 'src', 'action'];
    $tags = ['a', 'img', 'script', 'link', 'form'];

    // Build a regex pattern to match attributes within specified tags
    $pattern = '/<(' . implode('|', $tags) . ')\s+[^>]*?(?:' . implode('|', $attributes) . ')="([^"]+)"/i';

    // Callback to rewrite each matched URL
    $callback = function ($matches) use ($proxyHost) {
        $tag = $matches[1];       // The matched HTML tag (e.g., <a>, <form>)
        $originalUrl = $matches[2]; // The original URL in the attribute

        // Parse the URL to check for validity
        $parsedUrl = parse_url($originalUrl);

        // If the URL does not have a host (e.g., relative link), return it unchanged
        if (!isset($parsedUrl['host'])) {
            return $matches[0];
        }

        // Convert the domain (e.g., "example.com" to "example-com.proxy.com")
        $proxySubdomain = str_replace('.', '-', $parsedUrl['host']) . 'proxy' . $proxyHost;

        // Reconstruct the full proxy URL, replacing the original host
        $newUrl = preg_replace('/^https?:\/\/[^\/]+/', 'https://' . $proxySubdomain, $originalUrl);

        // Replace the original URL with the rewritten proxy URL
        return str_replace($originalUrl, $newUrl, $matches[0]);
    };

    // Apply the transformation to the HTML content
    return preg_replace_callback($pattern, $callback, $html);
}

function d(string $type, mixed $content = '', string $color = 'black', bool $force = false): void {
    if(!DEBUG && !$force) {
        return;
    }
    echo "<hr><h3 style='color: {$color}'>{$type}</h3><pre>" . print_r($content, true) . "</pre><hr />";
}

function debugStart(string $host): void {
    if(!DEBUG) {
        return;
    }
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head><title>' . $host . '</title></head>';
    echo '<body>';
}

function debugEnd(): void {
    if(!DEBUG) {
        return;
    }
    echo '</body>';
    echo '</html>';
}