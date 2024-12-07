<?php
// index.php

include_once '../fce.php';

$init = microtime(true);
d('Init', date('H:i:s'), 'green');

require '../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;

// Create the Slim app
$app = AppFactory::create();

// Add error middleware (for debugging, optional)
$app->addErrorMiddleware(true, true, true);

// Proxy route
$app->any('/{path:.*}', function (Request $request, Response $response, array $args) {
    $httpClient = new Client();

    try {
        // Extract target domain from host
        $host = $request->getUri()->getHost();
        d('Host', $host);

        $targetDomain = extractTargetDomain($host);
        d('Target domain', $targetDomain);

        // Build the target URI
        $uri = $request->getUri()->withHost($targetDomain)->withScheme('https');
        d('URI', $uri);

        // Forward the original request
        $guzzleRequest = [
            'headers' => $request->getHeaders(),
            'body' => $request->getBody(),
        ];

        $guzzleRequest['headers']['Host'] = $targetDomain;

        d('Method', $request->getMethod());
        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH'])) {
            $guzzleRequest['form_params'] = $request->getParsedBody();
        }

        d('Let\'s start :)', $guzzleRequest, 'orange');
        // Send the request using Guzzle
        $guzzleResponse = $httpClient->request(
            $request->getMethod(),
            (string)$uri,
            $guzzleRequest
        );
        d('Done :)', '', 'green');

        d('Response', $guzzleResponse);

        // Write response from the target back to the user
        $response = $response->withStatus($guzzleResponse->getStatusCode());

        foreach ($guzzleResponse->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $response = $response->withAddedHeader($name, $value);
            }
        }

        $response->getBody()->write($guzzleResponse->getBody()->getContents());
    } catch (Exception $e) {
        // Handle errors
        $response = $response->withStatus(500);
        d('Error',  $e->getMessage(), 'red');
        d('Error body', $response, 'red');

    }

    return $response;
});

$start = microtime(true);
d('Start', 'Took ' . $start - $init);
// Run the Slim application
$app->run();

d('End', 'Took ' . microtime(true) - $init);

