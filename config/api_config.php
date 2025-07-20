<?php

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (InvalidPathException $e) {
    var_dump("Error: .env file not found at " . __DIR__ . "/../");
} catch (Exception $e) {
    var_dump("An unexpected error occurred during Dotenv loading: " . $e->getMessage());
}

return [
    'base_url' => $_ENV['INPOST_API_BASE_URL'] ?? null,
    'token' => $_ENV['INPOST_API_TOKEN'] ?? null,
    'organization_id' => $_ENV['INPOST_ORGANIZATION_ID'] ?? null,
];