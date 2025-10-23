<?php

namespace Makis83\Helpers;

use Makis83\Helpers\traits\Charset;

/**
 * Helper class that provides methods for working with HTTP queries.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-10-23
 * Time: 17:05
 */
class Query {
    use Charset;


    /**
     * Parse sort sequence transferred from request query string.
     *
     * @param null|string $sortSequence String with comma-separated sort values
     * ```
     * first_name,-last_name,-status,email
     * ```
     * @return array<string, 'asc'|'desc'> Assoc array with sort column names as keys and sort orders as values
     * ```
     * ['first_name' => 'asc', 'last_name' => 'desc', 'status' => 'desc', 'email' => 'asc']
     * ```
     */
    public static function parseSortSequence(?string $sortSequence = null): array
    {
        // Check if sequence is empty
        if ('' === trim($sortSequence)) {
            return [];
        }

        // Split string into array
        $sortSequenceArr = array_map(
            static fn ($value): string => Text::fixSpaces($value),
            explode(',', $sortSequence)
        );

        // Initial sort array
        $sort = [];

        // Get charset
        $charset = static::getCharset();

        // Loop through sequence parts
        foreach ($sortSequenceArr as $part) {
            // Check if the first char is '-'
            if (str_starts_with($part, '-')) {
                $sort[mb_substr($part, 1, null, $charset)] = 'desc';
            } else {
                $sort[$part] = 'asc';
            }
        }

        // Return result array
        return $sort;
    }
}
