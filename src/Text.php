<?php

namespace Makis83\Helpers;

use Safe\Exceptions\SafeExceptionInterface;

/**
 *  Provides text related helper methods.
 *  Created by PhpStorm.
 *  User: max
 *  Date: 2025-09-15
 *  Time: 17:48
 */
class Text
{
    /**
     * Prepend the integer value with leading zeroes.
     * @param integer $number Number
     * @param non-negative-int $numberLength Total length of the number including leading zeroes
     * @return string Number with leading zeroes
     */
    public static function setLeadingZeroes(int $number, int $numberLength = 2): string
    {
        if ($numberLength <= 0) {
            return (string) $number;
        }

        return str_pad((string) $number, $numberLength, '0', STR_PAD_LEFT);
    }


    /**
     * Convert class name (with or without namespace) to ID.
     * @param string $class Class' full path
     * @return string Class' ID
     */
    public static function classNameToId(string $class): string
    {
        try {
            // Get the class' last part
            $class = \Safe\preg_replace('/^(\w+\\\)*/', '', $class);

            // Convert to ID
            return strtolower(\Safe\preg_replace('/(?<!^)[A-Z]/', '-$0', $class));
        } catch(SafeExceptionInterface) {
            return $class;
        }
    }


    /**
     * Fixes issues with spaces, NBSPs etc.
     * @param string $text Text to be fixed
     * @return string Prettified string
     * @see https://stackoverflow.com/a/51399843
     * @see https://www.utf8-chartable.de/unicode-utf8-table.pl?start=8192&number=128&utf8=string-literal
     */
    public static function fixSpaces(string $text): string
    {
        // Validate
        if ('' === trim($text)) {
            return '';
        }

        try {
            // Replace all non-regular SPACE symbols with a regular SPACE
            $text = \Safe\preg_replace(
                '/[\x{00A0}\x{180E}\x{2000}-\x{200F}\x{202F}\x{205F}\x{3000}\x{FEFF}]/u',
                ' ',
                $text
            );

            // Replace several spaces with one
            $text = \Safe\preg_replace('/ {2,}/', ' ', $text);
        } catch (SafeExceptionInterface) {}

        // Trim spaces
        $text = trim($text);

        // Fix value if it equals to SPACE
        return ' ' === $text ? '' : $text;
    }
}
