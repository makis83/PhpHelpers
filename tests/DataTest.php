<?php

namespace Makis83\Helpers\Tests;

use JsonException;
use Makis83\Helpers\Data;
use Makis83\Helpers\Text;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\FilesystemException;
use PHPUnit\Framework\Attributes\UsesMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for Data helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-16
 * Time: 15:04
 */
#[CoversClass(Data::class)]
#[UsesMethod(Text::class, 'fixSpaces')]
class DataTest extends TestCase
{
    /**
     * Data provider for 'testIsJson' method.
     *
     * @return array<string, array{0: null|bool|int|float|string, 1: bool}>
     */
    public static function isJsonDataProvider(): array
    {
        return [
            // Valid JSON
            'null' => [null, true],
            'boolean' => [true, true],
            'integer value' => [123, true],
            'valid JSON object' => ['{"key": "value"}', true],
            'valid JSON array' => ['["item1", "item2", "item3"]', true],
            'numeric string' => ['123', true],
            'boolean string' => ['true', true],

            // Invalid JSON
            'nullable string' => ['null', false],
            'string' => ['This is not JSON.', false],
            'invalid JSON object (missing closing brace)' => ['{"key": "value"', false],
            'invalid JSON array (Missing quotes around item3)' => ['["item1", "item2", item3]', false],
            'empty string' => ['', false],
            'string with only spaces' => ['   ', false]
        ];
    }


    /**
     * Test 'isJson' method.
     *
     * @param null|bool|int|float|string $test Data to be tested
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isJsonDataProvider')]
    final public function testIsJson(null|bool|int|float|string $test, bool $expected): void
    {
        $this->assertSame($expected, Data::isJson($test));
    }


    /**
     * Data provider for 'testFixDoubleEncodedUnicodeTokens' method.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function fixDoubleEncodedUnicodeTokensDataProvider(): array
    {
        return [
            'no double encoding' => ['{"key":"value"}', '{"key":"value"}'],
            'single double encoding' => [
                '{"text":"This is a test with special characters: \\\\u003C\\\\u003E"}',
                '{"text":"This is a test with special characters: \\u003C\\u003E"}'
            ],
            'multiple double encodings' => [
                '{"text":"Multiple double encodings: \\\\u003C\\\\u003E and again \\\\u003C\\\\u003E"}',
                '{"text":"Multiple double encodings: \\u003C\\u003E and again \\u003C\\u003E"}'
            ],
            'mixed content' => [
                '{"text":"Mixed content: normal text, \\\\u003C\\\\u003E, and more text."}',
                '{"text":"Mixed content: normal text, \\u003C\\u003E, and more text."}'
            ],
            'no unicode tokens' => ['{"message":"Hello, World!"}', '{"message":"Hello, World!"}'],
            'empty string' => ['', ''],
            'string without JSON structure' => [
                'Just a regular string with \\\\u003C\\\\u003E',
                'Just a regular string with \\u003C\\u003E'
            ]
        ];
    }


    /**
     * Test 'fixDoubleEncodedUnicodeTokens' method.
     *
     * @param string $test Data to be tested
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('fixDoubleEncodedUnicodeTokensDataProvider')]
    final public function testFixDoubleEncodedUnicodeTokens(string $test, string $expected): void
    {
        $this->assertSame($expected, Data::fixDoubleEncodedUnicodeTokens($test));
    }


    /**
     * Test 'fixDoubleEncodedUnicodeTokens' method with exception.
     *
     * @return void
     */
    final public function testFixDoubleEncodedUnicodeTokensException(): void
    {
        // Passing invalid regex pattern should return the original string
        $invalidRegex = '/[invald/';
        $this->assertSame(
            '{"text":"This is a test with special characters: \\\\u003C\\\\u003E"}',
            Data::fixDoubleEncodedUnicodeTokens(
                '{"text":"This is a test with special characters: \\\\u003C\\\\u003E"}',
                $invalidRegex
            )
        );
    }


    /**
     * Data provider for 'testJsonEncode' method.
     *
     * @return array<string, array{0: null|bool|int|float|string|array<int|string, mixed>, 1: string}>
     */
    public static function jsonEncodeDataProvider(): array
    {
        return [
            'empty string' => ['', '""'],
            'simple string' => ['simple text', '"simple text"'],
            'null value' => [null, 'null'],
            'boolean true' => [true, 'true'],
            'boolean false' => [false, 'false'],
            'empty array' => [[], '[]'],
            'simple array' => [['item1', 'item2', 'item3'], '["item1","item2","item3"]'],
            'associative array' => [['key' => 'value'], '{"key":"value"}'],
            'nested array' => [
                ['user' => ['name' => 'John', 'age' => 30]],
                '{"user":{"name":"John","age":30}}'
            ],
            'array with special characters' => [
                ['text' => 'This is a test with special characters: <>&\'"'],
                '{"text":"This is a test with special characters: \u003C\u003E\u0026\u0027\u0022"}'
            ],
            'array with multiple slashes' => [
                ['path' => 'C:\\Program Files\\App'],
                '{"path":"C:\\\\Program Files\\\\App"}'
            ]
        ];
    }


    /**
     * Test 'jsonEncode' method.
     *
     * @param null|bool|int|float|string|array<int|string, mixed> $test Data to be tested
     * @param string $expected Expected result
     * @return void
     * @throws JsonException
     */
    #[DataProvider('jsonEncodeDataProvider')]
    final public function testJsonEncode(null|bool|int|float|string|array $test, string $expected): void
    {
        $this->assertSame($expected, Data::jsonEncode($test));
    }


    /**
     * Test 'jsonEncode' method by passing a resource as a parameter.
     *
     * @return void
     * @throws JsonException
     */
    final public function testJsonEncodeThrowsExceptionOnResource(): void
    {
        // Get a resource handle
        try {
            $resource = \Safe\fopen('php://memory', 'rb');
        } catch (FilesystemException $exception) {
            $this->markTestIncomplete('Could not open resource: ' . $exception->getMessage());
        }

        // Try to encode a resource (which is not JSON-serializable)
        $this->expectException(JsonException::class);
        Data::jsonEncode($resource);

        // Close the resource handle
        try {
            \Safe\fclose($resource);
        } catch (FilesystemException $exception) {
            $this->markTestIncomplete('Could not close resource: ' . $exception->getMessage());
        }
    }


    /**
     * Test 'jsonEncode' method by passing a passing an array which exceeds max depth level.
     *
     * @return void
     * @throws JsonException
     */
    final public function testJsonEncodeThrowsExceptionOnMaxDepth(): void
    {
        $this->expectException(JsonException::class);
        Data::jsonEncode(['a' => ['b' => ['c' => ['d' => ['e' => 111]]]]], depth: 3);
    }


    /**
     * Data provider for 'testJsonDecode' method.
     *
     * @return array<string, array{0: string, 1: array<int|string, mixed>}>
     */
    public static function jsonDecodeDataProvider(): array
    {
        return [
            'empty array' => ['[]', []],
            'simple array' => ['["item1","item2","item3"]', ['item1', 'item2', 'item3']],
            'associative array' => ['{"key":"value"}', ['key' => 'value']],
            'nested array' => [
                '{"user":{"name":"John","age":30}}',
                ['user' => ['name' => 'John', 'age' => 30]]
            ],
            'array with special characters' => [
                '{"text":"This is a test with special characters: <>&\'\""}',
                ['text' => 'This is a test with special characters: <>&\'"']
            ],
            'array with special characters (unicode escaped)' => [
                '{"text":"This is a test with special characters: \u003C\u003E\u0026\u0027\u0022"}',
                ['text' => 'This is a test with special characters: <>&\'"']
            ],
            'array with multiple slashes' => [
                '{"path":"C:\\\\Program Files\\\\App"}',
                ['path' => 'C:\\Program Files\\App']
            ]
        ];
    }


    /**
     * Test 'jsonDecode' method.
     *
     * @param string $test Data to be tested
     * @param array<int|string, mixed> $expected Expected result
     * @return void
     * @throws JsonException
     */
    #[DataProvider('jsonDecodeDataProvider')]
    final public function testJsonDecode(string $test, array $expected): void
    {
        $this->assertSame($expected, Data::jsonDecode($test));
    }


    /**
     * Test 'jsonDecode' method by passing a non-JSON sequence.
     *
     * @return void
     * @throws JsonException
     */
    final public function testJsonDecodeThrowsExceptionOnNonJson(): void
    {
        $this->expectException(JsonException::class);
        Data::jsonDecode('This is not JSON.');
    }


    /**
     * Test 'jsonDecode' method by passing a non-JSON sequence.
     *
     * @return void
     * @throws JsonException
     */
    final public function testJsonDecodeThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(JsonException::class);
        Data::jsonDecode('{"key": "value"');
    }


    /**
     * Test 'jsonDecode' method by passing an object with depth level that exceeds the max allowed value.
     *
     * @return void
     * @throws JsonException
     */
    final public function testJsonDecodeThrowsExceptionOnMaxDepth(): void
    {
        $this->expectException(JsonException::class);
        Data::jsonDecode('{"a":{"b":{"c":{"d":111}}}}', depth: 3);
    }


    /**
     * Data provider for 'testValueToArray' method.
     *
     * @return array<string, array{
     *     0: null|bool|string|int|float|array<int|string, mixed>,
     *     1: array<int|string, mixed>
     * }>
     */
    public static function valueToArrayDataProvider(): array
    {
        return [
            'single string value' => ['singleValue', ['singleValue']],
            'comma-separated string values' => [
                'value1, value2, value3',
                ['value1', 'value2', 'value3']
            ],
            'string with extra spaces and delimiters' => [
                " value1  ,\xC2\xA0 value2 ,value3,\xE2\x80\x8F, , ",
                ['value1', 'value2', 'value3']
            ],
            'empty string' => ['   ', []],
            'null value' => [null, [null]],
            'boolean true' => [true, [true]],
            'boolean false' => [false, [false]],
            'integer value' => [123, [123]],
            'float value' => [45.67, [45.67]],
            'array input (should return as-is)' => [
                ['arrayValue1', 'arrayValue2'],
                ['arrayValue1', 'arrayValue2']
            ]
        ];
    }


    /**
     * Test 'valueToArray' method.
     *
     * @param null|bool|string|int|float|array<int|string, mixed> $test Data to be tested
     * @param array<int|string, mixed> $expected Expected result
     * @return void
     */
    #[DataProvider('valueToArrayDataProvider')]
    final public function testValueToArray(null|bool|string|int|float|array $test, array $expected): void
    {
        $this->assertSame($expected, Data::valueToArray($test));
    }
}
