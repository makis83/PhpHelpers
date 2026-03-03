<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\Geo;
use GeometryLibrary\PolyUtil;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for Geo helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2026-02-27
 * Time: 12:41
 */
#[CoversClass(Geo::class)]
class GeoTest extends TestCase
{
    /**
     * Data provider for 'testIsValidLatitude' method.
     *
     * @return array<string, array{0: int|float, 1: bool}>
     */
    public static function isValidLatitudeDataProvider(): array
    {
        return [
            'valid positive value' => [45.67, true],
            'valid negative value' => [-30.12, true],
            'valid zero value' => [0.0, true],
            'valid edge positive value' => [90.0, true],
            'valid edge negative value' => [-90.0, true],
            'invalid positive value' => [100.0, false],
            'invalid negative value' => [-100.0, false]
        ];
    }


    /**
     * Test 'isValidLatitude' method.
     *
     * @param int|float $latitude Latitude to check
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isValidLatitudeDataProvider')]
    final public function testIsValidLatitude(int|float $latitude, bool $expected): void
    {
        $this->assertSame($expected, Geo::isValidLatitude($latitude));
    }


    /**
     * Data provider for 'testIsValidLongitude' method.
     *
     * @return array<string, array{0: int|float, 1: bool}>
     */
    public static function isValidLongitudeDataProvider(): array
    {
        return [
            'valid positive value' => [45.67, true],
            'valid negative value' => [-30.12, true],
            'valid zero value' => [0.0, true],
            'valid edge positive value' => [180.0, true],
            'valid edge negative value' => [-180.0, true],
            'invalid positive value' => [200.0, false],
            'invalid negative value' => [-200.0, false]
        ];
    }


    /**
     * Test 'isValidLongitude' method.
     *
     * @param int|float $longitude Longitude
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isValidLongitudeDataProvider')]
    final public function testIsValidLongitude(int|float $longitude, bool $expected): void
    {
        $this->assertSame($expected, Geo::isValidLongitude($longitude));
    }


    /**
     * Data provider for 'testSimplifyDecodedPolyline' method.
     *
     * @return array<string, array{0: array<array{lat: int|float, lng: int|float}>, 1: int|float, 2: int}>
     */
    public static function simplifyDecodedPolylineDataProvider(): array
    {
        return [
            'simple straight line' => [
                [
                    ['lat' => 40.7120, 'lng' => -74.0060],
                    ['lat' => 40.7121, 'lng' => -74.0061],
                    ['lat' => 40.7122, 'lng' => -74.0062],
                ],
                100,
                2 // Should simplify to start and end points
            ],
            'complex polyline with default tolerance' => [
                [
                    ['lat' => 37.7749, 'lng' => -122.4194],
                    ['lat' => 37.7750, 'lng' => -122.4195],
                    ['lat' => 37.7751, 'lng' => -122.4196],
                    ['lat' => 37.7752, 'lng' => -122.4197],
                    ['lat' => 37.7753, 'lng' => -122.4198],
                ],
                20,
                2 // Should simplify significantly
            ],
            'polyline with low tolerance' => [
                [
                    ['lat' => 40.712, 'lng' => -74.006],
                    ['lat' => 40.713, 'lng' => -74.007],
                    ['lat' => 40.713, 'lng' => -74.008],
                ],
                1,
                3 // Low tolerance should keep more points
            ],
            'minimum two points' => [
                [
                    ['lat' => 51.5074, 'lng' => -0.1278],
                    ['lat' => 48.8566, 'lng' => 2.3522],
                ],
                20,
                2 // Should keep both points
            ],
        ];
    }


    /**
     * Test 'simplifyDecodedPolyline' method.
     *
     * @param array<array{lat: int|float, lng: int|float}> $decodedPolyline Decoded polyline
     * @param int|float $tolerance Tolerance for simplification
     * @param int $expectedCount Expected number of points after simplification
     * @return void
     */
    #[DataProvider('simplifyDecodedPolylineDataProvider')]
    final public function testSimplifyDecodedPolyline(
        array $decodedPolyline,
        int|float $tolerance,
        int $expectedCount
    ): void {
        $result = Geo::simplifyDecodedPolyline($decodedPolyline, $tolerance);
        $this->assertCount($expectedCount, $result);

        // Verify each point has 'lat' and 'lng'
        foreach ($result as $point) {
            $this->assertArrayHasKey('lat', $point);
            $this->assertArrayHasKey('lng', $point);
            $this->assertTrue(Geo::isValidLatitude($point['lat']));
            $this->assertTrue(Geo::isValidLongitude($point['lng']));
        }

        // Verify first and last points are preserved
        $this->assertEquals(round($decodedPolyline[0]['lat'], 6), round($result[0]['lat'], 6));
        $this->assertEquals(round($decodedPolyline[0]['lng'], 6), round($result[0]['lng'], 6));

        $this->assertEquals(
            round($decodedPolyline[count($decodedPolyline) - 1]['lat'], 6),
            round($result[count($result) - 1]['lat'], 6)
        );

        $this->assertEquals(
            round($decodedPolyline[count($decodedPolyline) - 1]['lng'], 6),
            round($result[count($result) - 1]['lng'], 6)
        );
    }


    /**
     * Test 'simplifyDecodedPolyline' with invalid input (less than 2 points).
     *
     * @return void
     */
    final public function testSimplifyDecodedPolylineWithInsufficientPoints(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Polyline must have at least two coordinates');

        Geo::simplifyDecodedPolyline([
            ['lat' => 0.0, 'lng' => 0.0],
        ]);
    }


    /**
     * Test 'simplifyDecodedPolyline' with invalid latitude.
     *
     * @return void
     */
    final public function testSimplifyDecodedPolylineWithInvalidLatitude(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid coordinate');

        Geo::simplifyDecodedPolyline([
            ['lat' => 0.0, 'lng' => 0.0],
            ['lat' => 100.0, 'lng' => 0.0], // Invalid latitude
        ]);
    }


    /**
     * Test 'simplifyDecodedPolyline' with invalid longitude.
     *
     * @return void
     */
    final public function testSimplifyDecodedPolylineWithInvalidLongitude(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid coordinate');

        Geo::simplifyDecodedPolyline([
            ['lat' => 0.0, 'lng' => 0.0],
            ['lat' => 0.0, 'lng' => 200.0], // Invalid longitude
        ]);
    }


    /**
     * Test 'simplifyEncodedPolyline' method.
     *
     * @return void
     */
    final public function testSimplifyEncodedPolyline(): void
    {
        // Encoded polyline representing a simple path
        // This is an example encoded polyline (San Francisco area)
        $encodedPolyline = '_p~iF~ps|U_ulLnnqC_mqNvxq`@';

        $result = Geo::simplifyEncodedPolyline($encodedPolyline);
        $this->assertNotEmpty($result);

        // Verify the result is a valid encoded polyline by decoding it
        $decoded = PolyUtil::decode($result);
        $this->assertIsArray($decoded);
        $this->assertGreaterThanOrEqual(2, count($decoded));

        // Verify all decoded points are valid
        foreach ($decoded as $point) {
            $this->assertTrue(Geo::isValidLatitude($point['lat']));
            $this->assertTrue(Geo::isValidLongitude($point['lng']));
        }
    }


    /**
     * Test 'simplifyEncodedPolyline' with custom tolerance.
     *
     * @return void
     */
    final public function testSimplifyEncodedPolylineWithCustomTolerance(): void
    {
        // Create a polyline with multiple points
        $decodedPolyline = [
            ['lat' => 37.7749, 'lng' => -122.4194],
            ['lat' => 37.7750, 'lng' => -122.4195],
            ['lat' => 37.7751, 'lng' => -122.4196],
            ['lat' => 37.7752, 'lng' => -122.4197],
            ['lat' => 37.7753, 'lng' => -122.4198],
        ];

        $encodedPolyline = PolyUtil::encode($decodedPolyline);

        // Test with high tolerance (more simplification)
        $resultHighTolerance = Geo::simplifyEncodedPolyline($encodedPolyline, 100);
        $decodedHighTolerance = PolyUtil::decode($resultHighTolerance);

        // Test with low tolerance (less simplification)
        $resultLowTolerance = Geo::simplifyEncodedPolyline($encodedPolyline, 1);
        $decodedLowTolerance = PolyUtil::decode($resultLowTolerance);

        // Low tolerance should preserve more or equal points than high tolerance
        $this->assertGreaterThanOrEqual(count($decodedHighTolerance), count($decodedLowTolerance));
    }


    /**
     * Test 'simplifyEncodedPolyline' preserves start and end points.
     *
     * @return void
     */
    final public function testSimplifyEncodedPolylinePreservesEndpoints(): void
    {
        $decodedPolyline = [
            ['lat' => 40.7128, 'lng' => -74.0060],
            ['lat' => 40.7129, 'lng' => -74.0061],
            ['lat' => 40.7130, 'lng' => -74.0062],
            ['lat' => 40.7131, 'lng' => -74.0063],
        ];

        $encodedPolyline = PolyUtil::encode($decodedPolyline);
        $result = Geo::simplifyEncodedPolyline($encodedPolyline);
        $decodedResult = PolyUtil::decode($result);

        // First and last points should be preserved
        $this->assertEquals(round($decodedPolyline[0]['lat'], 6), round($decodedResult[0]['lat'], 6));
        $this->assertEquals(round($decodedPolyline[0]['lng'], 6), round($decodedResult[0]['lng'], 6));
        $this->assertEquals(
            round($decodedPolyline[count($decodedPolyline) - 1]['lat'], 6),
            round($decodedResult[count($decodedResult) - 1]['lat'], 6)
        );
        $this->assertEquals(
            round($decodedPolyline[count($decodedPolyline) - 1]['lng'], 6),
            round($decodedResult[count($decodedResult) - 1]['lng'], 6)
        );
    }
}
