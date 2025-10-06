<?php

namespace Makis83\Helpers;

use JsonException;
use Safe\Exceptions\SafeExceptionInterface;

/**
 * Works with different data structures.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-16
 * Time: 14:07
 */
class Data
{
    /**
     * Check if the specified text is JSON.
     *
     * @param null|bool|int|float|string $data Data to be checked
     * @return bool Whether the string is a JSON sequence
     */
    public static function isJson(null|bool|int|float|string $data): bool
    {
        // Check if value is not string
        if (!is_string($data)) {
            return true;
        }

        // Check if data is empty
        if ('' === trim($data)) {
            return false;
        }

        // Try to decode data
        try {
            if (json_decode($data, true, 512, JSON_THROW_ON_ERROR)) {
                return true;
            }

            // Default value
            return false;
        } catch (JsonException) {
            return false;
        }
    }


    /**
     * Fix double slashes in JSON string.
     * For example, it converts ```\\\\u003C\\u003E``` to ```\\u003C\\u003E```
     *
     * @param string $jsonString JSON string
     * @param string $pattern Pattern to search for
     * @return string String with fixed slashes
     * @see https://regex101.com/r/fL9rGR/1
     */
    public static function fixDoubleEncodedUnicodeTokens(
        string $jsonString,
        string $pattern = '/\\\\(\\\\u[0-9a-f]{4})/iuU'
    ): string {
        try {
            return \Safe\preg_replace($pattern, '$1', $jsonString);
        } catch (SafeExceptionInterface) {
            return $jsonString;
        }
    }


    /**
     * Encode array as JSON.
     *
     * @param mixed $data Data to be encoded
     * @param int $options JSON options
     * @param positive-int $depth Maximum depth
     * @return string JSON-encoded string
     * @throws JsonException
     */
    public static function jsonEncode(
        mixed $data,
        int $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP,
        int $depth = 512
    ): string {
        // Encode
        $encodedData = json_encode($data, $options | JSON_THROW_ON_ERROR, $depth);

        // Fix Unicode tokens if needed
        return static::fixDoubleEncodedUnicodeTokens($encodedData);
    }


    /**
     * Decode the JSON-sequence.
     *
     * @param string $data Data to be decoded
     * @param bool $asArray Whether to return an associative array instead of an object
     * @param positive-int $depth Maximum depth
     * @return mixed Result data
     * @throws JsonException
     */
    public static function jsonDecode(string $data, bool $asArray = true, int $depth = 512): mixed
    {
        return json_decode($data, $asArray, $depth, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }


    /**
     * Convert value into array.
     *
     * @param array<int|string, mixed>|null|bool|int|float|string $value value
     * @param non-empty-string $delimiter delimiter for string values
     * @return array<null|bool|integer|float|string> result array
     */
    public static function valueToArray(array|null|bool|int|float|string $value, string $delimiter = ','): array
    {
        // Check if value is array
        if (is_array($value)) {
            return $value;
        }

        // Check if value is string
        if (is_string($value)) {
            // Check if value is empty
            if ('' === trim($value)) {
                return [];
            }

            // Split string by delimiter and trim each item
            $items = array_map('trim', explode($delimiter, Text::fixSpaces($value)));
            return array_filter($items, static fn($item) => '' !== $item);
        }

        // result
        return [$value];
    }
}
