<?php

namespace App\Service;

use App\Contracts\ApiClientInterface;
use App\Contracts\LoggerInterface;
use Exception;

class ShipmentService
{
    private ApiClientInterface $apiClient;
    private LoggerInterface $logger;

    /**
     * @param ApiClientInterface $apiClient
     * @param LoggerInterface $logger
     */
    public function __construct(ApiClientInterface $apiClient, LoggerInterface $logger)
    {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * Creates a shipment
     * @param array $shipmentData
     * @return array
     * @throws Exception
     */
    public function createShipment(array $shipmentData): array
    {
        $this->logger->log("Attempting to create shipment...");

        $createdShipment = $this->apiClient->createShipment($shipmentData);

        if (!isset($createdShipment['id'])) {
            throw new Exception("Shipment creation failed: No ID returned. Check logs for details.");
        }

        $shipmentId = $createdShipment['id'];

        $this->logger->log("Shipment created successfully! Shipment ID: " . $shipmentId);

        return $createdShipment;
    }

    /**
     * Gets a shipment data for provided ID
     * @param string $shipmentId
     * @return string
     */
    public function getShipmentStatus(string $shipmentId): string
    {
        $this->logger->log("Getting shipment status...");

        $response = $this->apiClient->getShipment($shipmentId);

        $shipment = reset($response['items']);

        return $shipment['status'];
    }
}