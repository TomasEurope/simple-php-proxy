<?php

declare(strict_types=1);

namespace App;

use Memcached;
use RuntimeException;

final class Searx
{
    private const string CACHE_KEY = 'search_engines_urls';
    private const int CACHE_TTL = 3600; // Cache for 1 hour
    private const string JSON_URL = 'https://searx.space/data/instances.json';

    private Memcached $memcached;

    /**
     * Constructor to initialize Memcached connection.
     */
    public function __construct()
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer('localhost', 11211);
    }

    /**
     * Get cached search engine URLs or fetch and cache them if not available.
     *
     * @return string Random engine URL
     * @throws RuntimeException If the JSON data cannot be retrieved or decoded.
     */
    public function getSearchEngineDomain(): string
    {
        // Check if data is already cached
        $urls = $this->memcached->get(self::CACHE_KEY);

        if (empty($urls) === true) {
            // Fetch and filter the data
            $urls = $this->fetchAndFilterUrls();

            // Cache the filtered data
            $this->memcached->set(self::CACHE_KEY, $urls, self::CACHE_TTL);
        }

        /*if (random_int(0, 5) === 0) {
            return 'en.wikipedia.org';
        }*/

        return parse_url($urls[array_rand($urls)])['host'];
    }

    /**
     * Fetch and filter URLs from the JSON data.
     *
     * @return array The filtered list of URLs.
     * @throws RuntimeException If the JSON data cannot be retrieved or decoded.
     */
    private function fetchAndFilterUrls(): array
    {
        $jsonData = file_get_contents(self::JSON_URL);

        if ($jsonData === false) {
            throw new RuntimeException('Failed to retrieve JSON data from ' . self::JSON_URL);
        }

        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode JSON: ' . json_last_error_msg());
        }

        if (!isset($data['instances'])) {
            throw new RuntimeException('Invalid JSON structure: "instances" key not found.');
        }

        // Filter instances based on network_type and uptimeDay
        $filteredUrls = [];
        foreach ($data['instances'] as $url => $instance) {
            if (
                isset($instance['network_type'])
                && isset($instance['uptime'])
                && isset($instance['uptime']['uptimeDay'])
                && $instance['network_type'] === 'normal'
                && $instance['uptime']['uptimeDay'] > 97
            ) {
                    $filteredUrls[] = $url;
            }
        }

        return $filteredUrls;
    }
}
