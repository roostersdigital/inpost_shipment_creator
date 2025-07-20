<?php

namespace App\Service;

use App\Contracts\ApiClientInterface;
use App\Contracts\LoggerInterface;
use Exception;

class DispatchOrderService
{
    private ApiClientInterface $apiClient;
    private LoggerInterface $logger;

    public function __construct(ApiClientInterface $apiClient, LoggerInterface $logger)
    {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * Creates Dispatch Order for provided shipment ID
     * @param array $dispatchOrderData
     * @param int $shipmentId
     * @return array
     * @throws Exception
     */
    public function createDispatchOrder(array $dispatchOrderData, int $shipmentId): array
    {
        $this->logger->log("Attempting to create dispatch order for shipment ID: " . $shipmentId . "...");

        $dispatchOrder = $this->apiClient->createDispatchOrder($dispatchOrderData, $shipmentId);

        if (!isset($dispatchOrder['id'])) {
            throw new Exception("Dispatch order creation failed: No Dispatch Order ID returned. Check logs for details.");
        }

        $this->logger->log("Dispatch Order successfully created! Dispatch Order ID: " . $dispatchOrder['id']);

        return $dispatchOrder;
    }
}