<?php

namespace Makis83\Helpers;

use RuntimeException;
use FilesystemIterator;
use UnexpectedValueException;
use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Safe\Exceptions\SafeExceptionInterface;

/**
 * Provides file related helper methods.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-13
 * Time: 22:21
 *
 */
class File
{
    /**
     * Normalize a file path to use forward slashes.
     *
     * @param non-empty-string $path Path to normalize
     * @return non-empty-string Normalized path
     */
    protected static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }


    /**
     * Get file extension from a file with the given path.
     *
     * @param non-empty-string $path Path to a file
     * @return string File extension
     */
    public static function fileExtension(string $path): string
    {
        // Handle complex extensions like `.tar.gz`
        $knownComplexExtensions = ['tar.gz', 'tar.bz2', 'tar.xz'];
        foreach ($knownComplexExtensions as $complexExt) {
            if (str_ends_with($path, ".$complexExt")) {
                return $complexExt;
            }
        }

        // Get file name with extension from the given path
        $fileName = basename(static::normalizePath($path));

        // Check if file name starts with dot and has no other dots
        // Such files are considered to have no extension (e.g. '.htaccess', '.editorconfig' etc.)
        if (str_starts_with($fileName, '.') && (!str_contains(mb_substr($fileName, 1), '.'))) {
            return '';
        }

        // Extract basic extension
        $lastDotPosition = mb_strrpos($path, '.');
        return ($lastDotPosition === false)
            ? ''
            : mb_substr($path, ($lastDotPosition + 1), null);
    }


    /**
     * Get file name from the given path.
     *
     * @param non-empty-string $path Path to a file
     * @param bool $withExtension Whether to include the file extension
     * @return string File name with or without extension
     */
    public static function fileName(string $path, bool $withExtension = true): string
    {
        // Get file name with extension from the given path
        $fileName = basename(static::normalizePath($path));

        // Return it if extension is needed
        if ($withExtension) {
            return $fileName;
        }

        // Get file extension
        $fileExtension = static::fileExtension($path);
        if ('' === $fileExtension) {
            return $fileName;
        }

        // Return file name without extension
        return mb_substr($fileName, 0, (mb_strlen($fileExtension) + 1) * -1);
    }


    /**
     * Check if the specified path is an absolute path.
     *
     * @param non-empty-string $path Path
     * @param bool $allowSchemes Whether to consider paths with schemes (like http://) as absolute
     * @return bool True if the path is an absolute path
     * @throws SafeExceptionInterface
     */
    public static function isAbsolutePath(string $path, bool $allowSchemes = true): bool
    {
        // Parse the path and check if it has a scheme or is absolute
        $parsed = \Safe\parse_url($path);

        // If it has a scheme, it's most likely absolute
        if ($allowSchemes && isset($parsed['scheme'])) {
            return true;
        }

        // Check for Unix absolute path
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Check for Windows absolute path
        if (('WINNT' === Server::getOs()) && \Safe\preg_match('~^[A-Z]:[\\\\/]|^[\\\\/]{2}~i', $path)) {
            return true;
        }

        return false;
    }


    /**
     * Create a directory with the given path if it doesn't exist.
     *
     * @param non-empty-string $path Absolute path to the directory that should be created
     * @param int $mode Directory mode (permissions), default is 0775
     * @param ?string $owner Directory owner
     * This will work only if the script is run with root privileges, since only root can change file ownership.
     * @param ?string $group Directory group
     * @return true True on success or if the directory already exists
     * @throws InvalidArgumentException|SafeExceptionInterface on errors
     */
    public static function ensureDirectory(
        string $path,
        int $mode = 0775,
        ?string $owner = null,
        ?string $group = null
    ): true {
        // Validate path
        if ('' === trim($path)) {
            throw new InvalidArgumentException('Invalid path: ' . $path);
        }

        // Ensure path is absolute
        if (!static::isAbsolutePath($path, false)) {
            throw new InvalidArgumentException('Path is not absolute: ' . $path);
        }

        // Normalize path and check if the given path already exists
        $normalizedPath = static::normalizePath($path);
        if (is_dir($normalizedPath)) {
            return true;
        }

        // Split path into parts
        $currentPath = '';
        $parts = array_filter(explode('/', $normalizedPath));

        // Loop through path parts
        foreach ($parts as $part) {
            // Add part to the current path
            $currentPath .= DIRECTORY_SEPARATOR . $part;

            // Check if current path exists
            if (!is_dir($currentPath)) {
                // Check if parent dir is writeable
                $parentDir = dirname($currentPath);
                if (!is_writable($parentDir)) {
                    throw new InvalidArgumentException(
                        "Cannot create directory '$currentPath': Parent directory is not writable"
                    );
                }

                // Create directory
                \Safe\mkdir($currentPath, $mode, true);

                // Set permissions and ownership
                try {
                    // Set dir mode
                    \Safe\chmod($currentPath, $mode);

                    // Set dir owner
                    if (null !== $owner) {
                        \Safe\chown($currentPath, $owner);
                    }

                    // Set dir owner group
                    if (null !== $group) {
                        \Safe\chgrp($currentPath, $group);
                    }
                } catch (SafeExceptionInterface $exception) {
                    // Remove the created directory on failure and throw the exception
                    \Safe\rmdir($currentPath);
                    throw $exception;
                }
            }
        }

        return true;
    }


    /**
     * Remove directory and all its files and subdirs recursively.
     *
     * @param non-empty-string $path Absolute path to the directory to remove
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on errors
     */
    public static function removeDirectory(string $path): void
    {
        // Validate path
        if ('' === trim($path)) {
            throw new InvalidArgumentException('Invalid path: ' . $path);
        }

        // Ensure path is absolute
        if (!static::isAbsolutePath($path, false)) {
            throw new InvalidArgumentException('Path is not absolute: ' . $path);
        }

        // Check if the directory exists
        $normalizedPath = static::normalizePath($path);
        if (!is_dir($normalizedPath)) {
            throw new InvalidArgumentException('Directory does not exist: ' . $normalizedPath);
        }

        // Check if the directory is writable
        if (!is_writable($normalizedPath)) {
            throw new InvalidArgumentException('Directory cannot be removed — Not writable: ' . $normalizedPath);
        }

        // Loop through files and directories inside the specified directory and remove them
        $objects = \Safe\scandir($normalizedPath);
        foreach ($objects as $object) {
            if ($object !== '.' && $object !== '..') {
                if (is_dir($normalizedPath . DIRECTORY_SEPARATOR . $object)) {
                    static::removeDirectory($normalizedPath . DIRECTORY_SEPARATOR . $object);
                } else {
                    \Safe\unlink($normalizedPath . DIRECTORY_SEPARATOR . $object);
                }
            }
        }

        // Remove the directory itself
        \Safe\rmdir($normalizedPath);
    }


    /**
     * Sanitizes a filename by removing characters that are problematic for filesystems.
     *
     * @param non-empty-string $fileName Filename to sanitize
     * @param non-empty-string $replacement Character to use as replacement for invalid characters
     * @return string Sanitized filename
     */
    public static function sanitizeFilename(string $fileName, string $replacement = '_'): string
    {
        // Fix possible problems with spaces
        $fileName = Text::fixSpaces($fileName);
        if ('' === $fileName) {
            return $replacement;
        }

        // Get file extension (if any)
        $fileExtension = self::fileExtension($fileName);
        $fileNameWOExtension = self::fileName($fileName, false);

        // Deal with the extension
        $maxLength = 255;
        if ('' !== trim($fileExtension)) {
            // Sanitize the extension
            try {
                $fileExtension = \Safe\preg_replace('/[^a-zA-Z0-9.]/', '', $fileExtension);
            } catch (SafeExceptionInterface) { // @codeCoverageIgnore
            }

            // If extension is longer than 10 chars, truncate it
            if (mb_strlen($fileExtension) > 10) {
                $fileExtension = mb_substr($fileExtension, 0, 10);
            }

            $maxLength -= mb_strlen($fileExtension) + 1;
        }

        // Step 1: Remove any directory separators (for security)
        $fileNameWOExtension = str_replace(['/', '\\'], $replacement, $fileNameWOExtension);

        // Step 2: Remove characters illegal in most operating systems
        // Windows disallows: \ / : * ? " < > |
        // macOS disallows: : /
        // Linux disallows: /
        try {
            $fileNameWOExtension = \Safe\preg_replace('/[<>:"\/|?*]/', $replacement, $fileNameWOExtension);
        } catch (SafeExceptionInterface) { // @codeCoverageIgnore
        }

        // Step 3: Remove control characters and other invisible / potentially problematic characters
        try {
            $fileNameWOExtension = \Safe\preg_replace('/[\x00-\x1F\x7F]/', '', $fileNameWOExtension);
        } catch (SafeExceptionInterface) { // @codeCoverageIgnore
        }

        // Step 4: Remove leading/trailing dots and spaces (problematic in Windows)
        $fileNameWOExtension = trim($fileNameWOExtension, " .\t\n\r\0\x0B");

        // Step 5: Remove emojis and other non-printable characters
        // Pattern to match various Unicode emoji ranges
        $emojiPattern = '/[\\x{1F600}-\\x{1F64F}]' . // Emoticons
            '|[\\x{1F300}-\\x{1F5FF}]' . // Miscellaneous Symbols and Pictographs
            '|[\\x{1F680}-\\x{1F6FF}]' . // Transport And Map Symbols
            '|[\\x{2600}-\\x{26FF}]' .   // Miscellaneous Symbols
            '|[\\x{2700}-\\x{27BF}]' .   // Dingbats
            '|[\\x{1F900}-\\x{1F9FF}]' . // Supplemental Symbols and Pictographs
            '|[\\x{1F1E6}-\\x{1F1FF}]/u'; // Regional Indicator Symbols (for flags)

        try {
            $fileNameWOExtension = \Safe\preg_replace($emojiPattern, '', $fileNameWOExtension);
        } catch (SafeExceptionInterface) { // @codeCoverageIgnore
        }

        // Step 6: Handle special cases (reserved names in Windows)
        $reservedNames = [
            'CON',
            'PRN',
            'AUX',
            'NUL',
            'COM1',
            'COM2',
            'COM3',
            'COM4',
            'COM5',
            'COM6',
            'COM7',
            'COM8',
            'COM9',
            'LPT1',
            'LPT2',
            'LPT3',
            'LPT4',
            'LPT5',
            'LPT6',
            'LPT7',
            'LPT8',
            'LPT9'
        ];

        if (in_array(mb_strtoupper($fileNameWOExtension), $reservedNames, true)) {
            $fileNameWOExtension .= $replacement;
        }

        // Step 7: If after sanitizing the filename is empty, provide a default
        if (empty($fileNameWOExtension)) {
            $fileNameWOExtension = $replacement;
        }

        // Step 8: Ensure filename isn't too long (most filesystems have limits)
        if (mb_strlen($fileNameWOExtension) > $maxLength) {
            $fileNameWOExtension = mb_substr($fileNameWOExtension, 0, $maxLength);
        }

        // Result
        if ('' !== trim($fileExtension)) {
            return $fileNameWOExtension . '.' . $fileExtension;
        }

        return $fileNameWOExtension;
    }


    /**
     * Throw exception if the dir path is invalid.
     *
     * @param non-empty-string $dir Path to directory
     * @return void
     * @throws InvalidArgumentException if the directory doesn't exist
     * @throws RuntimeException if the directory is not readable
     */
    private static function throwExceptionOnInvalidDir(string $dir): void
    {
        // Check if dir exists
        if (!is_dir($dir)) {
            throw new InvalidArgumentException('Path is not a directory: ' . $dir);
        }

        // Check if dir is not readable
        if (!is_readable($dir)) {
            throw new RuntimeException('Directory is not readable: ' . $dir);
        }
    }


    /**
     * Get directory size in bytes.
     *
     * @param non-empty-string $dir Path to directory
     * @return non-negative-int Dir size in bytes
     * @throws InvalidArgumentException if the directory doesn't exist
     * @throws RuntimeException if the directory is not readable
     * @throws UnexpectedValueException if the path cannot be found
     * @see https://stackoverflow.com/a/21409562
     */
    public static function dirSize(string $dir): int
    {
        // Throw an exception if dir is invalid
        self::throwExceptionOnInvalidDir($dir);

        // Initial size in bytes
        $size = 0;

        // Loop through nested files
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            ) as $file
        ) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        // Result
        return max($size, 0);
    }


    /**
     * Get number of files in the specified directory.
     *
     * @param non-empty-string $dir Path to directory
     * @param bool $recursive Whether to count files in subdirs too
     * @return non-negative-int Number of files in the dir
     * @throws InvalidArgumentException if the directory doesn't exist
     * @throws RuntimeException if the directory is not readable
     * @throws UnexpectedValueException if the path cannot be found
     */
    public static function countFiles(string $dir, bool $recursive = false): int
    {
        // Throw an exception if dir is invalid
        self::throwExceptionOnInvalidDir($dir);

        // Get iterator
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
        } else {
            $iterator = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
        }

        // Count files
        $count = 0;
        foreach ($iterator as $fsItem) {
            if ($fsItem->isFile()) {
                $count++;
            }
        }

        return max($count, 0);
    }


    /**
     * Get number of subdirectories in the specified directory.
     *
     * @param non-empty-string $dir Path to directory
     * @param bool $recursive Whether to count subdirs in subdirs too
     * @return non-negative-int Number of subdirs in the dir
     * @throws InvalidArgumentException if the directory doesn't exist
     * @throws RuntimeException if the directory is not readable
     * @throws UnexpectedValueException if the path cannot be found
     */
    public static function countSubDirs(string $dir, bool $recursive = false): int
    {
        // Throw an exception if dir is invalid
        self::throwExceptionOnInvalidDir($dir);

        // Get iterator
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $iterator = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
        }

        // Count dirs
        $count = 0;
        foreach ($iterator as $fsItem) {
            if ($fsItem->isDir()) {
                $count++;
            }
        }

        // Result
        return max($count, 0);
    }
}
