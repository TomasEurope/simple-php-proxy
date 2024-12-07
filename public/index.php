<?php
// index.php

// Include a custom utility file for debugging or other helper functions
include_once '../fce.php';

// Record the start time of the script execution
$init = microtime(true);
d('Init', date('H:i:s'), 'green');

// Autoload dependencies (Slim, Guzzle, and other packages)
require '../vendor/autoload.php';

// Use PSR-7 interfaces for handling HTTP requests and responses
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;

// Create a new Slim application instance
$app = AppFactory::create();

// Add error middleware to handle exceptions and debug errors (DEBUG constant should be defined)
$app->addErrorMiddleware(DEBUG, true, true);

// Define a catch-all route that matches any path
$app->any('/{path:.*}', function (Request $request, Response $response, array $args) {
    // Create a Guzzle HTTP client for forwarding requests
    $httpClient = new Client();

    try {
        // Extract the host from the incoming request
        $host = $request->getUri()->getHost();
        d('Host', $host);

        // Transform the host to get the target domain
        $targetDomain = extractTargetDomain($host);
        d('Target domain', $targetDomain);

        // Build the target URI with the extracted domain
        $uri = $request->getUri()->withHost($targetDomain)->withScheme('https');
        d('URI', $uri);

        // Prepare the headers and body for the forwarded request
        $guzzleRequest = [
            'headers' => $request->getHeaders(),
            'body' => $request->getBody(),
        ];

        // Set the Host header to match the target domain
        $guzzleRequest['headers']['Host'] = $targetDomain;

        d('Method', $request->getMethod());
        // Include the form data for POST, PUT, or PATCH requests
        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH'])) {
            $guzzleRequest['form_params'] = $request->getParsedBody();
        }

        d('Request', $guzzleRequest, 'orange');

        // Forward the request to the target domain using Guzzle
        $guzzleResponse = $httpClient->request(
            $request->getMethod(),
            (string)$uri,
            $guzzleRequest
        );

        d('Done :)', '', 'green');
        d('Response', $guzzleResponse);

        // Set the status code of the response from the target domain
        $response = $response->withStatus($guzzleResponse->getStatusCode());

        // Copy all headers from the Guzzle response to the Slim response
        foreach ($guzzleResponse->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $response = $response->withAddedHeader($name, $value);
            }
        }

        // Read the response body from the target domain
        $contents = $guzzleResponse->getBody()->getContents();

        if (!DEBUG) {
            // Rewrite hyperlinks in the HTML to route through the proxy
            $proxyHost = 'proxy.com'; // Replace with your actual proxy domain
            $contents = replaceUrlsWithProxy($contents, $proxyHost);
            // Write the body contents to the response (when not debugging)
            $response->getBody()->write($contents);
        } else {
            // Debugging: Output response details
            d('Response code', $response->getStatusCode(), $response->getStatusCode() === 200 ? 'green' : 'red');
            d('Response size', strlen($contents), strlen($contents) ? 'green' : 'red');
            d('Response preview', substr(htmlentities($contents), 0, 1000), strlen($contents) ? 'green' : 'red');
        }
    } catch (Exception $e) {
        // Handle exceptions during the proxy process
        $response = $response->withStatus(500);
        d('Error', $e->getMessage(), 'red');
        d('Error body', $response, 'red');
    }

    // Return the final response to the client
    return $response;
});

// Record the time when the Slim application starts
$start = microtime(true);
d('Start', 'Took ' . $start - $init);

// Run the Slim application to handle incoming requests
$app->run();

// Record the total execution time of the script
d('End', 'Took ' . microtime(true) - $init);
