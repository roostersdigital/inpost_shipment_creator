<?php

namespace App\Enums;

use ReflectionClass;

class InpostShipmentStatusEnum
{
    public const CREATED = 'created';
    public const OFFERS_PREPARED = 'offers_prepared';
    public const OFFER_SELECTED = 'offer_selected';
    public const CONFIRMED = 'confirmed';
    //TODO Add other statuses as needed from the InPost API documentation

    /**
     * Returns the human-readable title for a given InPost shipment status.
     *
     * @param string $status The API status string (e.g., 'created', 'confirmed').
     * @return string The title of the status, or 'Nieznany Status' if not found.
     */
    public static function getTitle(string $status): string
    {
        switch ($status) {
            case self::CREATED:
                return 'Przesyłka utworzona.';
            case self::OFFERS_PREPARED:
                return 'Przygotowano oferty.';
            case self::OFFER_SELECTED:
                return 'Oferta wybrana.';
            case self::CONFIRMED:
                return 'Przygotowana przez Nadawcę.';
            default:
                return 'Nieznany Status';
        }
    }

    /**
     * Returns a detailed description for a given InPost shipment status.
     *
     * @param string $status The API status string (e.g., 'created', 'confirmed').
     * @return string The description of the status, or 'Brak opisu.' if not found.
     */
    public static function getDescription(string $status): string
    {
        switch ($status) {
            case self::CREATED:
                return 'Przesyłka została utworzona, ale nie jest gotowa do nadania.';
            case self::OFFERS_PREPARED:
                return 'Oferty dla przesyłki zostały przygotowane.';
            case self::OFFER_SELECTED:
                return 'Klient wybrał jedną z zaproponowanych ofert.';
            case self::CONFIRMED:
                return 'Paczka wkrótce zostanie przekazana w nasze ręce, by trafić do Ciebie jak najszybciej.';
            default:
                return 'Brak szczegółowego opisu dla tego statusu.';
        }
    }

    /**
     * Checks if a given string is a valid, known InPost shipment status.
     *
     * @param string $status The status string to check.
     * @return bool True if the status is known, false otherwise.
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, [
            self::CREATED,
            self::OFFERS_PREPARED,
            self::OFFER_SELECTED,
            self::CONFIRMED,
        ]);
    }

    /**
     * Returns an array of all defined InPost shipment status constants.
     *
     * @return array<string>
     */
    public static function getAllStatuses(): array
    {
        $reflection = new ReflectionClass(__CLASS__);
        return array_values($reflection->getConstants());
    }
}