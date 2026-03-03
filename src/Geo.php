<?php

namespace Makis83\Helpers;

use Location\Polyline;
use Location\Coordinate;
use InvalidArgumentException;
use GeometryLibrary\PolyUtil;
use Location\Processor\Polyline\SimplifyDouglasPeucker;

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


    /**
     * Simplify a Polyline (decoded GeoJSON LineString) using the Douglas-Peucker algorithm.
     *
     * @param array<array{
     *     lat: int|float,
     *     lng: int|float
     * }> $decodedPolyline Decoded polyline (array of coordinates)
     * @param int|float $tolerance Tolerance for simplification (default: 20)
     * @return array{lat: float, lng: float}[] Simplified coordinates array (decoded GeoJSON LineString)
     */
    public static function simplifyDecodedPolyline(array $decodedPolyline, int|float $tolerance = 20): array
    {
        // Validate input
        if (count($decodedPolyline) < 2) {
            throw new InvalidArgumentException('Polyline must have at least two coordinates');
        }

        // Create Polyline object
        $polyline = new Polyline();

        foreach ($decodedPolyline as $coordinate) {
            // Validate coordinate
            if (
                !static::isValidLatitude($coordinate['lat']) ||
                !static::isValidLongitude($coordinate['lng'])
            ) {
                throw new InvalidArgumentException('Invalid coordinate');
            }

            // Add coordinate to Polyline
            $polyline->addPoint(new Coordinate($coordinate['lat'], $coordinate['lng']));
        }

        // Simplify polyline
        $simplifier = new SimplifyDouglasPeucker($tolerance);
        $simplifiedPolyline = $simplifier->simplify($polyline);

        // Return simplified coordinates
        $points = $simplifiedPolyline->getPoints();
        return array_map(static function ($point) {
            return [
                'lat' => $point->getLat(),
                'lng' => $point->getLng()
            ];
        }, $points);
    }


    /**
     * Simplify a Polyline (encoded GeoJSON LineString) using the Douglas-Peucker algorithm.
     *
     * @param string $encodedPolyline Encoded Polyline (GeoJSON LineString)
     * @param int|float $tolerance Tolerance for simplification (default: 20)
     * @return string Simplified encoded Polyline (GeoJSON LineString)
     */
    public static function simplifyEncodedPolyline(string $encodedPolyline, int|float $tolerance = 20): string
    {
        // Decode Polyline
        $decodedPolyline = PolyUtil::decode($encodedPolyline);

        // Simplify decoded Polyline
        $simplifiedDecodedPolyline = static::simplifyDecodedPolyline($decodedPolyline, $tolerance);

        // Encode simplified Polyline
        return PolyUtil::encode($simplifiedDecodedPolyline);
    }
}
