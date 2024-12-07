<?php

// TODO remove header from remote request
define('DEBUG', ($_SERVER['HTTP_X_FUCK'] === 'Yeah'));
function d($type, $content = '', $color = 'black'): void {
    if(!DEBUG) {
        return;
    }
    echo "<hr><h3 style='color: {$color}'>{$type}</h3><pre>" . print_r($content, true) . "</pre><hr />";
}

function extractTargetDomain(string $host): string {
    $pattern = '/^(?<domain>.+?)-(?<tld>.+?)\.proxy\.com$/';
    if (preg_match($pattern, $host, $matches)) {
        return $matches['domain'] . '.' . $matches['tld'];
    }
    throw new \RuntimeException('Invalid host format');
}