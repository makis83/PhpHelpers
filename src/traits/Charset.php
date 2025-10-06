<?php

namespace Makis83\Helpers\traits;

use Safe\Exceptions\SafeExceptionInterface;

/**
 * Provides data about current charset.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-10-04
 * Time: 20:08
 */
trait Charset
{
    /**
     * @var string $defaultCharset Default character set
     */
    private static string $defaultCharset = 'UTF-8';

    /**
     * @var string|null $charset Current character set
     */
    private static null|string $charset = null;


    /**
     * Get default character set.
     *
     * @return string Default character set
     */
    protected static function getCharset(): string
    {
        // Check if character set is already set
        if (null !== self::$charset) {
            return self::$charset;
        }

        // Get character set from ini
        try {
            self::$charset = \Safe\ini_get('default_charset');
        } catch (SafeExceptionInterface) {
            self::$charset = self::$defaultCharset;
        }

        return self::$charset;
    }
}
