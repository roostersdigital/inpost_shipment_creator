<?php

namespace App\Api;

use App\Contracts\ApiClientInterface;
use App\Contracts\LoggerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;

/**
 * InpostApiClient handles communication with the InPost API.
 */
class InpostApiClient implements ApiClientInterface
{
    private Client $client;
    private ?string $organizationId;
    private LoggerInterface $logger;

    /**
     * Constructor for InpostApiClient.
     *
     * @param array $config Configuration array containing base_url, token, and organization_id.
     * @param LoggerInterface $logger The logger instance to use for logging.
     * @throws InvalidArgumentException If required configuration values are missing.
     */
    public function __construct(array $config, LoggerInterface $logger)
    {
        if (empty($config['base_url']) || empty($config['token'])) {
            throw new InvalidArgumentException('InPost API base URL and token are required.');
        }

        $this->organizationId = $config['organization_id'] ?? null;
        $this->logger = $logger;

        $this->client = new Client([
            'base_uri' => $config['base_url'],
            'headers' => [
                'Authorization' => 'Bearer ' . $config['token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 10.0,
        ]);
    }

    /**
     * Makes a generic API request and handles the response.
     *
     * @param string $method The HTTP method (GET, POST, PUT, DELETE).
     * @param string $uri The API URI endpoint.
     * @param array $options Guzzle request options (e.g., 'json' for POST data).
     * @param string $context Description for logging (e.g., "Shipment Creation API").
     * @return array Decoded JSON response.
     * @throws RequestException On API request failure.
     * @throws Exception On other processing errors.
     * @throws GuzzleException
     */
    private function request(string $method, string $uri, array $options = [], string $context = 'API Request'): array
    {
        if (empty($this->organizationId)) {
            throw new Exception('Organization ID is required to create a shipment. Please set INPOST_ORGANIZATION_ID in your .env file.');
        }

        $this->logger->log("Sending $method request to $uri...");
        try {
            $response = $this->client->request($method, $uri, $options);
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            $this->logger->logApiResponse($context, $statusCode, $responseBody);

            if ($statusCode >= 400) {
                throw new Exception("API returned an error status $statusCode for $uri: " . json_encode($responseBody));
            }

            return $responseBody;

        } catch (RequestException $e) {
            $errorResponse = null;
            if ($e->hasResponse()) {
                $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            $this->logger->logError($context, $e, $errorResponse);
            throw $e;
        } catch (Exception $e) {
            $this->logger->logError($context, $e);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     * @throws Exception|GuzzleException
     */
    public function createShipment(array $shipmentData): array
    {
        $uri = "v1/organizations/$this->organizationId/shipments";
        return $this->request('POST', $uri, ['json' => $shipmentData], 'API - Create Shipment');
    }

    /**
     * @inheritDoc
     * @param array $dispatchOrderData
     * @param int $shipmentId
     * @throws Exception|GuzzleException
     */
    public function createDispatchOrder(array $dispatchOrderData, int $shipmentId): array
    {
        $dispatchOrderData['shipments'] = [(string)$shipmentId];

        $uri = "v1/organizations/$this->organizationId/dispatch_orders";
        return $this->request('POST', $uri, ['json' => $dispatchOrderData], 'API - Create Dispatch Order');
    }

    /**
     * @throws Exception|GuzzleException
     */
    public function getShipment(int $shipmentId): array
    {
        $uri = "v1/organizations/$this->organizationId/shipments?id=$shipmentId";
        return $this->request('GET', $uri, [], 'API - Get Shipment');
    }
}