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
     * @param string $text String to be checked
     * @return bool Whether the string is a JSON sequence
     */
    public static function isJson(string $text): bool
    {
        // Check if data exists
        if ('' === trim($text)) {
            return false;
        }

        // Check if value is 'null'
        if ('null' === $text) {
            return true;
        }

        // Try to decode data
        try {
            if (json_decode($text, true, 512, JSON_THROW_ON_ERROR)) {
                return true;
            }

            // Default value
            return false;
        } catch (JsonException) {
            return false;
        }
    }


    /**
     * Encode array as JSON.
     *
     * @param array<int|string, mixed> $data Data array to be encoded
     * @param int $options JSON options
     * @return string JSON-encoded string
     * @throws JsonException|SafeExceptionInterface
     */
    public static function jsonEncode(
        array $data,
        int $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ): string {
        // Encode
        try {
            $encodedData = json_encode($data, $options | JSON_THROW_ON_ERROR);
        } catch (JsonException $oException) {
            throw new JsonException($oException->getMessage());
        }

        // Remove double slashes
        $sPattern = '/\\\\(\\\\u[0-9a-f]{4})/iuU';
        return \Safe\preg_replace($sPattern, '$1', $encodedData);
    }


    /**
     * Decode the JSON-sequence.
     * @param string $data Data to be decoded
     * @param bool $asArray Whether to return an associative array instead of an object
     * @return mixed Result data
     * @throws JsonException
     */
    public static function jsonDecode(string $data, bool $asArray = true): mixed
    {
        return json_decode($data, $asArray, 512, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
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
            return array_filter($items, static fn ($item) => '' !== $item);
        }

        // result
        return [$value];
    }
}
