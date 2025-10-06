<?php

namespace Makis83\Helpers;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use Safe\Exceptions\PosixException;

/**
 * Provides server related helper methods.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-24
 * Time: 19:14
 */
class Server
{
    /**
     * Get the operating system PHP is running on.
     *
     * @return string The operating system name
     */
    public static function getOs(): string
    {
        return PHP_OS;
    }


    /**
     * Throws an exception if the current OS is Windows or the POSIX extension is not loaded.
     *
     * @throws RuntimeException if the current OS is Windows, or the POSIX extension is not loaded
     */
    private static function throwExceptionIfNeeded(): void
    {
        // Check if the current OS is Windows
        if (strncasecmp(static::getOs(), 'WIN', 3) === 0) {
            throw new RuntimeException('This function is not available on Windows operating systems.');
        }

        // Check if the POSIX extension is loaded
        if (!extension_loaded('posix')) {
            throw new RuntimeException('The POSIX extension is not loaded.');
        }
    }


    /**
     * Get the effective user ID of the current process.
     *
     * @return int The effective user ID
     * @throws RuntimeException if the current OS is Windows, or the POSIX extension is not loaded
     */
    public static function getEuid(): int
    {
        self::throwExceptionIfNeeded();
        return posix_geteuid();
    }


    /**
     * Get user information by ID.
     *
     * @param null|int $uid User ID (optional, if null it will be fetched)
     * @return array{name: string, passwd: string, uid: int, gid: int, gecos: string, dir: string, shell: string}
     * User information array
     * @throws Exception if the current OS is Windows, or the POSIX extension is not loaded, or no user found
     */
    public static function getUserInfoByUid(?int $uid = null): array
    {
        // Check environment
        self::throwExceptionIfNeeded();

        // Get user ID if not provided
        if (null === $uid) {
            $uid = static::getEuid();
        }

        // Get user information for the UID
        try {
            return \Safe\posix_getpwuid($uid);
        } catch (PosixException $exception) {
            throw new InvalidArgumentException("No user found for UID $uid.", 0, $exception);
        }
    }


    /**
     * Get user name for the specified user ID.
     *
     * @param null|int $uid User ID (optional, if null it will be fetched)
     * @return string user name
     * @throws Exception if the current OS is Windows, or the POSIX extension is not loaded, or no user found
     */
    public static function getUserNameByUid(?int $uid = null): string
    {
        // Check environment
        self::throwExceptionIfNeeded();

        // Get user ID if not provided
        if (null === $uid) {
            $uid = static::getEuid();
        }

        // Get user info
        $userInfo = static::getUserInfoByUid($uid);
        return $userInfo['name'];
    }
}
