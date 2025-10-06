<?php

namespace Makis83\Helpers;

use Random\RandomException;
use Makis83\Helpers\traits\Charset;
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
    use Charset;


    /**
     * Prepend the integer value with leading zeroes.
     *
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
     *
     * @param non-empty-string $class Class' full path
     * @return string Class' ID
     */
    public static function classNameToId(string $class): string
    {
        try {
            // Get the class' last part
            $class = \Safe\preg_replace('/^(\w+\\\)*/', '', $class);

            // Convert to ID
            return strtolower(\Safe\preg_replace('/(?<!^)[A-Z]/', '-$0', $class));
        } catch (SafeExceptionInterface) { // @codeCoverageIgnore
            return $class; // @codeCoverageIgnore
        }
    }


    /**
     * Fixes issues with spaces, NBSPs etc.
     *
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
        } catch (SafeExceptionInterface) { // @codeCoverageIgnore
        }

        // Trim spaces
        $text = trim($text);

        // Fix value if it equals to SPACE
        return ' ' === $text ? '' : $text;
    }


    /**
     * Generate text random sequence.
     *
     * @param positive-int $length Sequence length
     * @param 'alpha'|'numeric'|'alphanumeric' $collection Chars collection ('alpha', 'numeric' or 'alphanumeric')
     * @param string $case Chars case ('lower', 'upper' or 'both')
     * @return string Random sequence of symbols
     * @throws RandomException if an appropriate source of randomness cannot be found
     */
    public static function random(
        int $length = 10,
        string $collection = 'alphanumeric',
        string $case = 'both'
    ): string {
        // Get string with available chars
        if ('alpha' === $collection) {
            if ('lower' === $case) {
                $chars = 'abcdefghijklmnopqrstuvwxyz';
            } elseif ('upper' === $case) {
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            } else {
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            }
        } elseif ('numeric' === $collection) {
            $chars = '1234567890';
        } elseif ('lower' === $case) {
            $chars = 'abcdefghijklmnopqrstuvwxyz1234567890';
        } elseif ('upper' === $case) {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        } else {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        }

        // Generate random string
        $charsLength = strlen($chars);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $chars[random_int(0, $charsLength - 1)];
        }

        // Result
        return $randomString;
    }


    /**
     * Check if a string contains only ASCII characters.
     *
     * @param string $string String to check
     * @return bool True if string contains only ASCII characters, false otherwise
     * @see https://stackoverflow.com/a/53939317/9653787
     */
    public static function isAscii(string $string): bool
    {
        return ('ASCII' === mb_detect_encoding($string, 'ASCII', true));
    }


    /**
     * Check if a string contains only Unicode characters.
     *
     * @param string $string String to check
     * @return bool True if string contains only ASCII characters, false otherwise
     * @see https://stackoverflow.com/a/53939317/9653787
     */
    public static function isUnicode(string $string): bool
    {
        return ('UTF-8' === mb_detect_encoding($string, 'UTF-8', true));
    }


    /**
     * Check if the given string is encoded in Punycode.
     *
     * @param string $string String to check
     * @return bool True if the string is encoded in Punycode, false otherwise
     * @see https://stackoverflow.com/a/53939317/9653787
     * @see https://en.wikipedia.org/wiki/Punycode
     */
    public static function isPunycode(string $string): bool
    {
        // Check if the string has only ASCII characters
        if (!static::isAscii($string) || !static::isUnicode($string)) {
            return false;
        }

        // Check if the string starts with 'xn--'
        if (!str_starts_with($string, 'xn--')) {
            return false;
        }

        // Try to decode the string
        $decoded = idn_to_utf8($string);
        return false !== $decoded;
    }


    /**
     * Transliterate a string (from Cyrillic to Latin letters).
     *
     * @param string $string String to transliterate
     * @return string Transliterated string
     */
    public static function transliterate(string $string): string
    {
        // Validate
        if ('' === trim($string)) {
            return '';
        }

        // Mappings array
        $mappings = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'yo',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'ts',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'shch',
            'ъ' => '',
            'ы' => 'i',
            'ь' => '\'',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
            '\'' => '',
            '`' => '',

            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'Yo',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'Ts',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Shch',
            'Ъ' => '',
            'Ы' => 'I',
            'Ь' => '\'',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
        ];

        // Transliterate the string
        return strtr($string, $mappings);
    }


    /**
     * Trim the specified sub-string from the beginning of the string.
     *
     * @param string $string Original string
     * @param string $remove Part to be removed
     * @return string Trimmed string
     * @see https://stackoverflow.com/a/4517270/9653787
     */
    public static function ltrim(string $string, string $remove): string
    {
        // Validate
        if ('' === trim($string)) {
            return '';
        }

        if ('' === $remove) {
            return $string;
        }

        // Trim the substring
        if (str_starts_with($string, $remove)) {
            $string = mb_substr($string, mb_strlen($remove), mb_strlen($string));
        }

        // result
        return $string;
    }


    /**
     * Trim the specified sub-string from the end of the string.
     *
     * @param string $string Original string
     * @param string $remove Part to be removed
     * @return string Trimmed string
     * @see https://stackoverflow.com/a/47689812/9653787
     */
    public static function rtrim(string $string, string $remove): string
    {
        // Validate
        if ('' === trim($string)) {
            return '';
        }

        if ('' === $remove) {
            return $string;
        }

        // Get length of the trimmed text
        $length = mb_strlen($remove);

        // Trim
        if ($remove === mb_substr($string, -$length)) {
            $string = mb_substr($string, 0, -$length);
        }

        // Result
        return $string;
    }


    /**
     * Generate slug (alias) from the given string.
     *
     * @param string $string String to generate alias from
     * @param bool $allowUppercase Allow uppercase characters in the alias
     * @param bool $allowUnicode Allow Unicode characters in the alias
     * @param string $delimiter Delimiter
     * @param positive-int $length Max length
     * @return string Alias
     * @throws SafeExceptionInterface if an error occurs during the PCRE operation
     */
    public static function slug(
        string $string,
        bool $allowUppercase = false,
        bool $allowUnicode = false,
        string $delimiter = '-',
        int $length = 255
    ): string {
        // Fix spaces
        $string = static::fixSpaces($string);

        // Convert to lowercase
        if (!$allowUppercase) {
            $string = mb_strtolower($string);
        }

        // Replace new lines with spaces
        $string = str_replace(["\r\n", "\n", "\r"], ' ', $string);

        // Change underscores to delimiters
        if ('_' !== $delimiter) {
            $string = str_replace('_', $delimiter, $string);
        }

        // Remove the dangerous chars
        if ($allowUnicode) {
            $pattern = '/[^\p{L}\p{N}\s]+/iu';
        } else {
            $pattern = '/[^a-z0-9\s]+/i';

            // Transliterate string
            $string = static::transliterate($string);
        }

        $string = \Safe\preg_replace($pattern, $delimiter, $string);

        // Fix multiple spaces
        $pattern = '/\s+/iu';
        $string = \Safe\preg_replace($pattern, $delimiter, $string);

        // Fix multiple delimiters
        $pattern = '/(?:' . preg_quote($delimiter, '/') . ')+/iu';
        $string = \Safe\preg_replace($pattern, $delimiter, $string);

        // Trim the string
        $string = mb_substr($string, 0, $length);

        // Trim delimiters
        return static::ltrim(static::rtrim($string, $delimiter), $delimiter);
    }


    /**
     * Detect whether the string has new line symbol.
     *
     * @param string $string String to check
     * @return boolean Whether the string has new line symbol
     */
    public static function hasNewLine(string $string): bool
    {
        // Validate
        if ('' === trim($string)) {
            return false;
        }

        // Process
        foreach (["\r", "\n", "\r\n", "\n\r"] as $token) {
            if (str_contains($string, $token)) {
                return true;
            }
        }

        // Default case
        return false;
    }


    /**
     * Make intro from the given text.
     *
     * @param string $text Full text
     * @param int $length Intro length (chars)
     * @param null|string $trailingChars Trailing characters to add to the intro
     * @param bool $useFirstLineOnly Whether to use only first line of text
     * @return string Text intro
     * @throws SafeExceptionInterface if an error occurs during the PCRE operation
     * @see https://regex101.com/r/eZfGId/1
     */
    public static function intro(
        string $text,
        int $length = 200,
        ?string $trailingChars = null,
        bool $useFirstLineOnly = false
    ): string {
        // Set default trailing characters
        if (null === $trailingChars) {
            $trailingChars = '…';
        }

        // Strip tags
        $text = strip_tags($text);

        // Fix spaces
        $text = static::fixSpaces($text);

        // Check if text length is within the limit
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        // Remove new lines
        if ($useFirstLineOnly && static::hasNewLine($text)) {
            $firstLine = strtok($text, "\n");
            if (false !== $firstLine) {
                $text = $firstLine;
            }
        } else {
            $text = str_replace(["\n", "\r", "\r\n", "\n\r"], ' ', $text);
        }

        // Generate intro
        $pattern = '/^(.{' . $length . ',})[\p{Po}|\s]++/iuU';
        \Safe\preg_match($pattern, $text, $matches);

        // Detect if intro exists
        if (isset($matches[1])) {
            $text = $matches[1] . $trailingChars;
        }

        // Result
        return $text;
    }


    /**
     * Split the text into parts.
     *
     * @param string $text Text to split
     * @param integer $maxPartLength Maximum part length (chars)
     * @return array<int, string> Array of text parts
     * @throws SafeExceptionInterface if an error occurs during the PCRE operation
     */
    public static function split(string $text, int $maxPartLength = 1000): array
    {
        // Fix spaces
        $text = static::fixSpaces($text);

        // Check if text length is within the limit
        if (mb_strlen($text) <= $maxPartLength) {
            return [$text];
        }

        // Split the text into sentences
        if (Html::isHTML($text)) {
            $sentences = \Safe\preg_split('/(<\/?\w+[^>]*>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        } else {
            $sentences = \Safe\preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        }

        // Loop through sentences
        $chunks = [];
        $currentChunk = '';
        foreach ($sentences as $sentence) {
            // If the current sentence is an HTML tag, add it to the current chunk
            if (\Safe\preg_match('/<\/?[^>]+>/', $sentence)) {
                $currentChunk .= $sentence;
                continue;
            }

            // Calculate the length of the current chunk with the new sentence
            $newChunkLength = mb_strlen($currentChunk) + mb_strlen($sentence);

            // If the new chunk length is within the limit, add the sentence to the current chunk
            if ($newChunkLength <= $maxPartLength) {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
            } else {
                // Otherwise, add the current chunk to the list of chunks and start a new one
                $chunks[] = $currentChunk;
                $currentChunk = $sentence;
            }
        }

        // Add the last chunk to the list of chunks
        if ($currentChunk) {
            $chunks[] = $currentChunk;
        }

        // Result
        return $chunks;
    }


    /**
     * Remove corrupted multibyte characters from the given string.
     *
     * @param string $string String to remove corrupted characters from
     * @return string String without corrupted characters
     * @throws SafeExceptionInterface if an error occurs during the PCRE operation
     */
    public static function removeCorruptedChars(string $string): string
    {
        // Get the charset
        $charset = static::getCharset();
        $newCharset = 'UTF-8' === $charset ? 'Windows-1251' : 'UTF-8';

        // Make double convert for removing the illegal chars
        $converted = \Safe\iconv($charset, $newCharset . '//IGNORE', $string);
        return \Safe\iconv($newCharset, $charset . '//IGNORE', $converted);
    }
}
