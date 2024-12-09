<?php

/**
 * The App class is responsible for setting up and running the Slim application,
 * forwarding HTTP requests, and handling incoming HTTP requests.
 * It utilizes various helper methods to perform debugging and logging activities.
 *
 * @file App.php
 *
 * @author  Tomas <studnasoft@gmail.com>
 * @license https://github.com/tomascc MIT
 */

namespace App;

// Use PSR-7 interfaces for handling HTTP requests and responses.
use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Factory\AppFactory;

use function microtime;
use function htmlentities;
use function in_array;
use function strlen;
use function date;

final readonly class App
{
    /**
     * Construct :)
     *
     * @param Helper $helper Helper object.
     */
    public function __construct(private Helper $helper)
    {
    }


    /**
     * Initializes and runs the Slim application to handle incoming HTTP requests.
     * This method sets up the application, initializes debugging, handles request forwarding,
     * and measures execution time for performance analysis.
     *
     * @return void
     */
    public function start(): void
    {
        // Record the start time of the script execution.
        $init = microtime(true);
        if (is_string($_SERVER['HTTP_HOST']) === false || empty(trim($_SERVER['HTTP_HOST'])) === true) {
            throw new RuntimeException();
        }

        // Init debug.
        $this->helper->debugStart($_SERVER['HTTP_HOST']);
        $this->helper->d('Init', date('H:i:s'), 'green');

        // Create a new Slim application instance.
        $app = AppFactory::create();

        // Add error middleware to handle exceptions and debug errors.
        $app->addErrorMiddleware(true, true, true);

        // Define a catch-all route that matches any path.
        $app->any(
            '/{path:.*}',
            function (Request $request, Response $response) {
                // Create a Guzzle HTTP client for forwarding requests.
                $httpClient = new Client();

                try {
                    // Extract the host from the incoming request.
                    $host = $request->getUri()->getHost();

                    // Transform the host to get the target domain.
                    $targetDomain = $this->helper->extractTargetHost($host);

                    // Build the target URI with the extracted domain.
                    $uri = $request->getUri()->withHost($targetDomain)->withScheme('https');
                    $this->helper->d('User request', $uri);

                    // Prepare the headers and body for the forwarded request.
                    $guzzleRequest = [
                        'headers' => $request->getHeaders(),
                        'body'    => $request->getBody(),
                    ];

                    // Set the Host header to match the target domain.
                    $guzzleRequest['headers']['Host'] = $targetDomain;

                    $this->helper->d('Method', $request->getMethod());
                    // Include the form data for POST, PUT, or PATCH requests.
                    if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH']) === true) {
                        $guzzleRequest['form_params'] = $request->getParsedBody();
                    }

                    $this->helper->d('Server Request', $guzzleRequest);

                    // Forward the request to the target domain using Guzzle.
                    $guzzleResponse = $httpClient->request(
                        $request->getMethod(),
                        (string) $uri,
                        $guzzleRequest
                    );

                    $this->helper->d('Done :)', '', 'green');
                    $this->helper->d('Response', $guzzleResponse);

                    // Set the status code of the response from the target domain.
                    $response = $response->withStatus($guzzleResponse->getStatusCode());

                    // Copy all headers from the Guzzle response to the Slim response.
                    foreach ($guzzleResponse->getHeaders() as $name => $values) {
                        foreach ($values as $value) {
                            $response = $response->withAddedHeader($name, $value);
                        }
                    }

                    // Read the response body from the target domain.
                    $contents = $guzzleResponse->getBody()->getContents();

                    if ($this->helper->config->debug === false) {
                        // Rewrite hyperlinks in the HTML to route through the proxy.
                        $proxyHost = 'proxy.com';
                        // Replace with your actual proxy domain.
                        $contents = $this->helper->replaceUrlsWithProxy($contents, $proxyHost);
                        // Write the body contents to the response (when not debugging).
                        $response->getBody()->write($contents);
                    } else {
                        // Debugging: Output response details.
                        $this->helper->d(
                            'Response code',
                            $response->getStatusCode(),
                            $response->getStatusCode() === 200 ? 'green' : 'red'
                        );
                        $this->helper->d(
                            'Response size',
                            strlen($contents),
                            $contents !== '' ? 'green' : 'red'
                        );
                        $this->helper->d(
                            'Response preview',
                            substr(htmlentities($contents), 0, 1000),
                            $contents !== '' ? 'green' : 'red'
                        );
                    }
                } catch (Exception $e) {
                    // Handle exceptions during the proxy process.
                    $response = $response->withStatus(599);
                    $this->helper->d('Error', $e->getMessage(), 'red', true);
                    $this->helper->d('Error body', $response, 'red', true);
                }//end try

                // Return the final response to the client.
                return $response;
            }
        );

        // Record the time when the Slim application starts.
        $start = microtime(true);
        $this->helper->d('Start', 'Took ' . ($start - $init));

        // Run the Slim application to handle incoming requests.
        $app->run();

        // Record the total execution time of the script.
        $this->helper->d('End', 'Took ' . (microtime(true) - $init));

        // End debug.
        $this->helper->debugEnd();
    }
}
