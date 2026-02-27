<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\Geo;
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
}
