<?php

namespace Makis83\Helpers\Tests;

use JsonException;
use Makis83\Helpers\Data;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\SafeExceptionInterface;

/**
 * Tests for Data helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-16
 * Time: 15:04
 */
class DataTest extends TestCase
{
    /**
     * Test 'isJson' method.
     * @return void
     */
    final public function testIsJson(): void
    {
        // Valid JSON
        $this->assertTrue(Data::isJson('{"key": "value"}'));
        $this->assertTrue(Data::isJson('["item1", "item2", "item3"]'));
        $this->assertTrue(Data::isJson('123'));
        $this->assertTrue(Data::isJson('true'));
        $this->assertTrue(Data::isJson('null'));

        // Invalid JSON
        $this->assertFalse(Data::isJson('This is not JSON.'));
        $this->assertFalse(Data::isJson('{"key": "value"')); // Missing closing brace
        $this->assertFalse(Data::isJson('["item1", "item2", item3]')); // Missing quotes around item3
        $this->assertFalse(Data::isJson('')); // Empty string
        $this->assertFalse(Data::isJson('   ')); // String with only spaces
    }


    /**
     * Test 'jsonEncode' method.
     * @return void
     */
    final public function testJsonEncode(): void
    {
        try {
            // Empty array
            $this->assertEquals(
                '[]', Data::jsonEncode([])
            );

            // Simple array
            $this->assertEquals(
                '["item1","item2","item3"]',
                Data::jsonEncode(['item1', 'item2', 'item3'])
            );

            // Associative array
            $this->assertEquals(
                '{"key":"value"}', Data::jsonEncode(['key' => 'value'])
            );

            // Nested array
            $this->assertEquals(
                '{"user":{"name":"John","age":30}}',
                Data::jsonEncode(['user' => ['name' => 'John', 'age' => 30]])
            );

            // Array with special characters
            $this->assertEquals(
                '{"text":"This is a test with special characters: \u003C\u003E\u0026\u0027\u0022"}',
                Data::jsonEncode(['text' => 'This is a test with special characters: <>&\'"'])
            );

            // Array with multiple slashes
            $this->assertEquals(
                '{"path":"C:\\\\Program Files\\\\App"}',
                Data::jsonEncode(['path' => 'C:\\Program Files\\App'])
            );
        } catch (JsonException|SafeExceptionInterface $exception) {
            $this->markTestSkipped('Could not run test: ' . $exception->getMessage());
        }
    }


    /**
     * Test 'jsonDecode' method.
     * @return void
     */
    final public function testJsonDecode(): void
    {
        // Not a valid JSON
        $this->expectException(JsonException::class);
        Data::jsonDecode('This is not JSON.');

        try {
            // Empty array
            $this->assertEquals(
                [], Data::jsonDecode('[]')
            );

            // Simple array
            $this->assertEquals(
                ['item1', 'item2', 'item3'],
                Data::jsonDecode('["item1","item2","item3"]')
            );

            // Associative array
            $this->assertEquals(
                ['key' => 'value'], Data::jsonDecode('{"key":"value"}')
            );

            // Nested array
            $this->assertEquals(
                ['user' => ['name' => 'John', 'age' => 30]],
                Data::jsonDecode('{"user":{"name":"John","age":30}}')
            );

            // Array with special characters
            $this->assertEquals(
                ['text' => 'This is a test with special characters: <>&\'"'],
                Data::jsonDecode('{"text":"This is a test with special characters: <>&\'\""}')
            );

            $this->assertEquals(
                ['text' => 'This is a test with special characters: <>&\'"'],
                Data::jsonDecode('{"text":"This is a test with special characters: \u003C\u003E\u0026\u0027\u0022"}')
            );

            // Array with multiple slashes
            $this->assertEquals(
                ['path' => 'C:\\Program Files\\App'],
                Data::jsonDecode('{"path":"C:\\\\Program Files\\\\App"}')
            );
        } catch (JsonException $exception) {
            $this->fail('Could not run test: ' . $exception->getMessage());
        }
    }


    /**
     * Test 'valueToArray' method.
     * @return void
     */
    final public function testValueToArray(): void
    {
        // Single value
        $this->assertEquals(
            ['singleValue'],
            Data::valueToArray('singleValue')
        );

        // Comma-separated values
        $this->assertEquals(
            ['value1', 'value2', 'value3'],
            Data::valueToArray('value1, value2, value3')
        );

        // Values with extra spaces and delimiters
        $this->assertEquals(
            ['value1', 'value2', 'value3'],
            Data::valueToArray(" value1  ,\xC2\xA0 value2 ,value3,\xE2\x80\x8F, , ")
        );

        // Empty string
        $this->assertEquals(
            [],
            Data::valueToArray('   ')
        );

        // Null and bool values
        $this->assertEquals(
            [null],
            Data::valueToArray(null)
        );

        $this->assertEquals(
            [true],
            Data::valueToArray(true)
        );

        // Array input (should return as-is)
        $this->assertEquals(
            ['arrayValue1', 'arrayValue2'],
            Data::valueToArray(['arrayValue1', 'arrayValue2'])
        );
    }
}
