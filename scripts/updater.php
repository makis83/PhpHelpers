#!/usr/bin/env php
<?php
/**
 * This script is used to update local domains list.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-10-01
 * Time: 17:15
 */

use Safe\Exceptions\FilesystemException;

require_once __DIR__. '/../vendor/autoload.php';

/**
 * Downloads a file from a URL and saves it to a local file.
 * @param non-empty-string $url URL of the file to download
 * @param non-empty-string $file Path to the file to save
 * @return void
 * @throws FilesystemException
 */
function updateFile(string $url, string $file): void {
    // Retrieve the content of the remote file
    $fileContent = \Safe\file_get_contents($url);

    // Save the content to the local file
    \Safe\file_put_contents($file, $fileContent);
}

// Download the latest domains list
try {
    updateFile(
        'https://publicsuffix.org/list/public_suffix_list.dat',
        __DIR__ . '/../src/domains/public_suffix_list.dat'
    );

    updateFile(
        'https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
        __DIR__ . '/../src/domains/tlds-alpha-by-domain.txt'
    );

    echo 'Local domains list updated successfully.';
} catch (FilesystemException $exception) {
    echo 'Failed to update local domains list: ' . $exception->getMessage();
}
