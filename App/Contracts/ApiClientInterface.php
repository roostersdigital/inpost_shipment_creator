<?php

namespace App\Contracts;

interface ApiClientInterface
{
    /**
     * Creates a shipment.
     * @param array $shipmentData
     * @return array The created shipment data from the API.
     */
    public function createShipment(array $shipmentData): array;

    /**
     * Creates a dispatch order for a given shipment.
     * @param array $dispatchOrderData
     * @param int $shipmentId
     * @return array The dispatch order data from the API.
     */
    public function createDispatchOrder(array $dispatchOrderData, int $shipmentId): array;


    /**
     * Gets a chosen shipment data
     * @param int $shipmentId
     * @return array
     */
    public function getShipment(int $shipmentId): array;
}