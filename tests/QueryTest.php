<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\Text;
use Makis83\Helpers\Query;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\UsesMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for Query helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-10-23
 * Time: 17:40
 */
#[CoversClass(Query::class)]
#[UsesMethod(Text::class, 'fixSpaces')]
class QueryTest extends TestCase
{
    /**
     * Data provider for 'testParseSortSequence' method.
     *
     * @return array<string, array{0: string, 1: array<string, 'asc'|'desc'>}>
     */
    public static function parseSortSequenceDataProvider(): array
    {
        return [
            'empty string' => ['', []],
            'one sort parameter' => ['first_name', ['first_name' => 'asc']],
            'valid string' => [
                'first_name,-last_name,-status,email',
                ['first_name' => 'asc', 'last_name' => 'desc', 'status' => 'desc', 'email' => 'asc']
            ],
            'string with special characters' => [
                " first_name,  -last_name,\xE2\x80\x8F\xe2\x80\x8a-status,email",
                ['first_name' => 'asc', 'last_name' => 'desc', 'status' => 'desc', 'email' => 'asc']
            ]
        ];
    }


    /**
     * Test 'fixSpaces' method.
     *
     * @param string $sort Sort sequence
     * @param array<string, 'asc'|'desc'> $expected Expected result
     * @return void
     */
    #[DataProvider('parseSortSequenceDataProvider')]
    final public function testParseSortSequence(string $sort, array $expected): void
    {
        $this->assertEquals($expected, Query::parseSortSequence($sort));
    }
}
