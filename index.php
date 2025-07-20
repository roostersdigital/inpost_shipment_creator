<?php

use App\Api\InpostApiClient;
use App\Enums\InpostShipmentStatusEnum;
use App\Logger\FileLogger;
use App\Service\DispatchOrderService;
use App\Service\ShipmentService;

/*
|--------------------------------------------------------------------------
| Require App Bootstrapping
|--------------------------------------------------------------------------
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'config/api_config.php';

header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| Initialize loggers and setup logging to file
|--------------------------------------------------------------------------
*/

// Initialize Loggers
$fileLogger = new FileLogger(__DIR__ . '/storage/logs/log.json');

// Helper functions to log info and error to file
$log = function (string $message) use ($fileLogger) {
    $fileLogger->log($message);
};

$logError = function (string $context, Throwable $e, ?array $errorResponse = null, int $httpStatusCode = 500) use ($fileLogger) {
    $fileLogger->logError($context, $e, $errorResponse);
    http_response_code($httpStatusCode);
};

/*
|--------------------------------------------------------------------------
| Get POST data for request and validate it
|--------------------------------------------------------------------------
|
|   Use php://input as a source if you want to fire from .http/shipments.http
|
*/

$source = 'payload.json';
//$source = 'php://input';

$input = json_decode(file_get_contents($source), true);
$shipmentData = $input['shipment'];
$dispatchOrderData = $input['dispatch_order'];

if (json_last_error() !== JSON_ERROR_NONE || !is_array($shipmentData)) {
    $logError("API Endpoint", new Exception("Invalid JSON input."), null, 400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
    exit();
}

/*
|--------------------------------------------------------------------------
| Load and validate API configuration
|--------------------------------------------------------------------------
*/

$config = include __DIR__ . '/config/api_config.php';

if (empty($config['token']) || empty($config['organization_id'])) {
    $logError(
        "Configuration",
        new Exception("InPost API token or Organization ID is missing."),
        ['details' => 'Please ensure INPOST_API_TOKEN and INPOST_ORGANIZATION_ID are set in your .env file.'],
        500
    );
    echo json_encode(['status' => 'error', 'message' => 'Server configuration error.']);
    exit();
}

/*
|--------------------------------------------------------------------------
| Fire Shipment Creator
|--------------------------------------------------------------------------
*/

try {
    // Initialize API Client and Service
    $apiClient = new InpostApiClient($config, $fileLogger);
    $shipmentService = new ShipmentService($apiClient, $fileLogger);
    $dispatchOrderService = new DispatchOrderService($apiClient, $fileLogger);

    $log("Received POST request. Starting InPost shipment and courier order process...");

    // 1. Create the Shipment
    $createdShipment = $shipmentService->createShipment($shipmentData);
    $shipmentId = $createdShipment['id'];
    $log("Shipment initiated with ID: " . $shipmentId);

    // 2. Wait for Shipment to be CONFIRMED
    $maxAttempts = 10; // Maximum number of times to check
    $delaySeconds = 6; // Seconds to wait between checks
    $isConfirmed = false;
    $confirmedShipmentDetails = null;

    for ($i = 0; $i < $maxAttempts; $i++) {
        $log("Checking shipment status for ID " . $shipmentId . " (attempt " . ($i + 1) . "/" . $maxAttempts . ")...");
        sleep($delaySeconds); // Pause execution before polling

        $currentStatus = $shipmentService->getShipmentStatus($shipmentId);
        $log("Current status of shipment " . $shipmentId . ": " . $currentStatus);

        if ($currentStatus === InpostShipmentStatusEnum::CONFIRMED) {
            $log("Shipment " . $shipmentId . " is CONFIRMED!");

            $isConfirmed = true;
            break; // Exit loop, shipment is ready
        }

        if ($i === $maxAttempts - 1) {
            throw new Exception("Shipment " . $shipmentId . " did not confirm within the expected time. Final status: " . $currentStatus);
        }
    }

    if (!$isConfirmed) {
        // This line would only be reached if the loop somehow broke without confirming,
        // but the throw above should prevent it. This is a safety net.
        throw new Exception("Shipment could not be confirmed for dispatch.");
    }

    // 3. Create Dispatch Order (only if shipment is confirmed)

    // Ensure dispatchOrderData includes the correct structure for shipments, which requires the ID and the newly obtained tracking number.
    $dispatchOrderData['shipments'][] = [(string)$shipmentId];

    $result = $dispatchOrderService->createDispatchOrder($dispatchOrderData, $shipmentId);
    $log("Dispatch Order created successfully for shipment " . $shipmentId);

    // Return Success Response
    http_response_code(200);

    echo json_encode([
        'status' => 'success',
        'message' => 'Shipment created and courier ordered successfully.',
        'data' => [
            'shipment_id' => $shipmentId ?? null,
            'shipment_details' => $createdShipment ?? null,
            'dispatch_order_id' => $result['id'] ?? null,
            'dispatch_order_details' => $result ?? null,
        ]
    ]);

    $log("Process completed successfully and response sent.");

} catch (InvalidArgumentException $e) {
    // Log configuration errors and bad inputs
    $logError("API Logic (Invalid Argument)", $e, null, 400);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input provided. Please check your data.']);
} catch (Exception $e) {
    // Catch any general exceptions from the service layer or API client, including our custom thrown exception for timeout
    $logError("API Logic (Runtime Error)", $e, null, 500);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred during the shipment process. ' . $e->getMessage()]);
} catch (Throwable $e) {
    // Catch all throwables in PHP 7+ for ultimate safety
    $logError("API Logic (Unhandled Throwable)", $e, null, 500);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred. Please try again later.']);
}