<?php

namespace Makis83\Helpers;

/**
 * Provides geo related helper methods.
 * Created by PhpStorm.
 * User: max
 * Date: 2026-02-27
 * Time: 12:31
 */
class Geo
{
    /**
     * Check if the given latitude is valid.
     *
     * @param int|float $latitude Latitude
     * @return bool True if the latitude is valid
     */
    public static function isValidLatitude(int|float $latitude): bool
    {
        return (-90.0 <= $latitude) && ($latitude <= 90.0);
    }


    /**
     * Check if the given longitude is valid.
     *
     * @param int|float $longitude Longitude
     * @return bool True if the longitude is valid
     */
    public static function isValidLongitude(int|float $longitude): bool
    {
        return (-180.0 <= $longitude) && ($longitude <= 180.0);
    }
}
