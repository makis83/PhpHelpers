<?php

namespace Makis83\Helpers\Tests;

use RuntimeException;
use Makis83\Helpers\File;
use Makis83\Helpers\Text;
use Random\RandomException;
use Makis83\Helpers\Server;
use InvalidArgumentException;
use UnexpectedValueException;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\SafeExceptionInterface;
use PHPUnit\Framework\Attributes\UsesMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for File helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-14
 * Time: 15:39
 */
#[CoversClass(File::class)]
#[UsesMethod(Text::class, 'fixSpaces')]
#[UsesMethod(Text::class, 'random')]
#[UsesMethod(Server::class, 'getOs')]
class FileTest extends TestCase
{
    /**
     * Get random directory path.
     *
     * @return non-empty-string Path to test dir
     */
    private function getRandomDirPath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpunit_tests_' . uniqid('', true);
    }


    /**
     * Create test dir.
     *
     * @param string $parentDir Parent dir
     * @return void
     */
    private function createTestDir(string $parentDir): void
    {
        // Check if parent dir can be created
        $tempDir = sys_get_temp_dir();
        if (!is_dir($tempDir)) {
            $this->markTestSkipped('System temp directory does not exist');
        }

        if (!is_writable($tempDir)) {
            $this->markTestSkipped('System temp directory is not writable');
        }

        if (is_dir($parentDir)) {
            $this->markTestSkipped('Test directory already exists');
        }

        // Creating test environment
        try {
            // Create main dir
            \Safe\mkdir($parentDir, 0755, true);

            // Create nested dirs
            $dirs = [
                'dir1',
                'dir2' . DIRECTORY_SEPARATOR . 'sub-dir-1',
                'dir2' . DIRECTORY_SEPARATOR . 'sub-dir-2' . DIRECTORY_SEPARATOR . 'sub-sub-dir-1',
                'dir3' . DIRECTORY_SEPARATOR . 'sub-dir-1'
            ];

            foreach ($dirs as $dir) {
                \Safe\mkdir($parentDir . DIRECTORY_SEPARATOR . $dir, 0755, true);
            }

            // Create 10 files each 10 bytes size
            for ($i = 0; $i < 10; $i++) {
                \Safe\file_put_contents(
                    $parentDir . DIRECTORY_SEPARATOR . $dirs[array_rand($dirs)] . DIRECTORY_SEPARATOR .
                    Text::random(6, 'alpha', 'lower') . '.txt',
                    Text::random()
                );
            }
        } catch (SafeExceptionInterface|RandomException $exception) {
            $this->markTestSkipped('Could not create test directory: ' . $exception->getMessage());
        }
    }


    /**
     * Data provider for 'testFileExtension' method.
     *
     * @return array<string, array{0: non-empty-string, 1: string}>
     */
    public static function fileExtensionDataProvider(): array
    {
        return [
            'no extension' => ['binfile', ''],
            'hidden file' => ['.htaccess', ''],
            'simple extension' => ['document.txt', 'txt'],
            'complex extension' => ['archive.tar.gz', 'tar.gz'],
            'url with double extension' => ['ftp://server.tech/archive.tar.bz2', 'tar.bz2'],
            'windows path with double extension' => ['C:\\path\\to\\file.tar.gz', 'tar.gz']
        ];
    }


    /**
     * Test 'fileExtension' method.
     *
     * @param non-empty-string $path Path to a file
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('fileExtensionDataProvider')]
    final public function testFileExtension(string $path, string $expected): void
    {
        $this->assertEquals($expected, File::fileExtension($path));
    }


    /**
     * Data provider for 'testFileName' method.
     *
     * @return array<string, array{0: non-empty-string, 1: bool, 2: string}>
     */
    public static function fileNameDataProvider(): array
    {
        return [
            'no extension' => ['binfile', true, 'binfile'],
            'no extension, with exclude extension option' => ['binfile', false, 'binfile'],
            'hidden file' => ['.htaccess', true, '.htaccess'],
            'simple file' => ['document.txt', true, 'document.txt'],
            'simple file with exclude extension option' => ['document.txt', false, 'document'],
            'complex extension' => ['archive.tar.gz', true, 'archive.tar.gz'],
            'complex extension with exclude extension option' => ['archive.tar.gz', false, 'archive'],
            'url with double extension' => ['ftp://server.tech/archive.tar.bz2', true, 'archive.tar.bz2'],
            'url with double extension with exclude extension option' => [
                'ftp://server.tech/archive.tar.bz2',
                false,
                'archive'
            ],
            'windows path with backslashes and double extension' => [
                "C:\\path\\to\\file.tar.gz",
                true,
                'file.tar.gz'
            ],
            'windows path with backslashes and double extension with exclude extension option' => [
                "C:\\path\\to\\file.tar.gz",
                false,
                'file'
            ],
            'windows path with slashes and double extension' => [
                "C:/path/to/file.tar.gz",
                true,
                'file.tar.gz'
            ],
            'windows path with slashes and double extension with exclude extension option' => [
                "C:/path/to/file.tar.gz",
                false,
                'file'
            ]
        ];
    }


    /**
     * Test 'fileName' method with various paths.
     *
     * @param non-empty-string $path Path to a file
     * @param bool $withExtension Whether to include the file extension
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('fileNameDataProvider')]
    final public function testFileName(string $path, bool $withExtension, string $expected): void
    {
        $this->assertEquals($expected, File::fileName($path, $withExtension));
    }


    /**
     * Data provider for 'testIsAbsolutePath' method.
     *
     * @return array<string, array{0: non-empty-string, 1: bool, 2: bool}>
     */
    public static function isAbsolutePathDataProvider(): array
    {
        return [
            'empty path' => ['   ', true, false],
            'absolute unix path' => ['/var/www/html', true, true],
            'absolute windows path with backslashes (scheme enabled)' => ['C:\\Program Files\\App', true, true],
            'absolute windows path with slashes (scheme enabled)' => ['C:/Program Files/App', true, true],
            'relative path with slashes' => ['relative/path/to/file', true, false],
            'relative path with backslashes' => ['relative\\path\\to\\file', false, false],
            'relative windows path' => ['another\\relative\\path', true, false],
            'path with scheme (http)' => ['http://example.com/file', true, true],
            'path with scheme (https)' => ['https://example.com/file', true, true],
            'path with scheme (ftp)' => ['ftp://example.com/file', true, true],
            'path with scheme (http) not allowed' => ['http://example.com/file', false, false],
            'path with scheme (https) not allowed' => ['https://example.com/file', false, false],
            'path with scheme (ftp) not allowed' => ['ftp://example.com/file', false, false]
        ];
    }


    /**
     * Test 'pathIsAbsolute' method with various paths.
     *
     * @param non-empty-string $path Path
     * @param bool $allowSchemes Whether to consider paths with schemes (like http://) as absolute
     * @param bool $expected Expected result
     * @return void
     * @throws SafeExceptionInterface
     */
    #[DataProvider('isAbsolutePathDataProvider')]
    final public function testIsAbsolutePath(string $path, bool $allowSchemes, bool $expected): void
    {
        $this->assertEquals($expected, File::isAbsolutePath($path, $allowSchemes));
    }


    /**
     * Test 'ensureDirectory' method by creating temporary directories.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testEnsureDirectory(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Test creating a new directory with default permissions and owner/group
        $nestedDir = $testDir . DIRECTORY_SEPARATOR . 'test';
        File::ensureDirectory($nestedDir);
        $this->assertDirectoryExists($nestedDir);

        // Test ensuring the created directory exists
        File::ensureDirectory($nestedDir);
        $this->assertDirectoryExists($nestedDir);
        \Safe\rmdir($nestedDir);

        // Test creating directory with specific permissions
        File::ensureDirectory($nestedDir, 0750);
        $this->assertDirectoryExists($nestedDir);
        $filePerms = substr(sprintf('%o', \Safe\fileperms($nestedDir)), -3);
        $this->assertEquals('750', $filePerms);
        \Safe\rmdir($nestedDir);

        // Remove test directory
        File::removeDirectory($testDir);

        // Test creating invalid directory path
        // $this->expectException(Throwable::class);
        // File::ensureDirectory($this->testBaseDir . DIRECTORY_SEPARATOR . "\0invalid");
    }


    /**
     * Test 'ensureDirectory' method by passing an empty dir path as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testEnsureDirectoryThrowsExceptionOnEmptyDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::ensureDirectory('   ');
    }


    /**
     * Test 'ensureDirectory' method by passing a relative dir as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testEnsureDirectoryThrowsExceptionOnRelativeDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::ensureDirectory('relative/path');
    }


    /**
     * Test 'ensureDirectory' method by passing a wrong dir mode.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testEnsureDirectoryThrowsExceptionOnWrongMode(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Test
        $this->expectException(InvalidArgumentException::class);
        File::ensureDirectory($testDir . DIRECTORY_SEPARATOR . 'test', 9999);

        // Remove test directory
        File::removeDirectory($testDir);
    }


    /**
     * Test 'ensureDirectory' method by passing a wrong group owner.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface|RandomException on failure
     */
    final public function testEnsureDirectoryThrowsExceptionOnWrongGroupOwner(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Test
        $this->expectException(SafeExceptionInterface::class);
        File::ensureDirectory($testDir . DIRECTORY_SEPARATOR . 'test', group: Text::random());

        // Remove test directory
        File::removeDirectory($testDir);
    }


    /**
     * Test 'removeDirectory' method with various cases.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testRemoveDirectory(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Remove empty directory
        $emptyDir = $testDir . DIRECTORY_SEPARATOR . 'dir1';
        File::removeDirectory($emptyDir);
        $this->assertDirectoryDoesNotExist($emptyDir);

        // Remove non-empty dir
        $nonEmptyDir = $testDir . DIRECTORY_SEPARATOR . 'dir2';
        File::removeDirectory($nonEmptyDir);
        $this->assertDirectoryDoesNotExist($nonEmptyDir);

        // Remove test directory
        File::removeDirectory($testDir);
        $this->assertDirectoryDoesNotExist($testDir);
    }


    /**
     * Test 'removeDirectory' method by passing a non-existing dir as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testRemoveDirectoryThrowsExceptionOnNonExistingDir(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Remove non-existing directory
        $this->expectException(InvalidArgumentException::class);
        File::removeDirectory($testDir . DIRECTORY_SEPARATOR . 'non_existing_dir');

        // Remove test directory
        File::removeDirectory($testDir);
    }


    /**
     * Test 'removeDirectory' method by passing an empty dir as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testRemoveDirectoryThrowsExceptionOnEmptyDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::removeDirectory('   ');
    }


    /**
     * Test 'removeDirectory' method by passing a relative dir as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testRemoveDirectoryThrowsExceptionOnRelativeDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::removeDirectory('relative/path');
    }


    /**
     * Data provider for 'testSanitizeFilename' method.
     *
     * @return array<string, array{0: non-empty-string, 1: non-empty-string, 2: string}>
     */
    public static function sanitizeFilenameDataProvider(): array
    {
        // Generate very long file name
        $veryLongFileName = str_repeat('a', 300) . '.txt';
        $veryLongSanitizedFileName = str_repeat('a', 255 - 4) . '.txt'; // 255 minus length of '.txt'

        // Config array
        return [
            'empty filename' => ['   ', '_', '_'],
            'valid filename' => ['valid_filename.txt', '_', 'valid_filename.txt'],
            'filename with spaces and special characters' => [
                ' !! 🤬 invalid ¹²% _файлнаме??.txt ',
                '_',
                '!!  invalid ¹²% _файлнаме__.txt'
            ],
            'filename with reserved name (Windows)' => ['aux', '_', 'aux_'],
            'filename with only invalid characters' => ['???', '_', '___'],
            'filename with mixed valid and invalid characters' => [
                'my*inva|lid:fi<le>name?.txt',
                '-',
                'my-inva-lid-fi-le-name-.txt'
            ],
            'filename with leading and trailing spaces' => [
                '   leading_and_trailing_spaces   .txt',
                '_',
                'leading_and_trailing_spaces.txt'
            ],
            'filename with multiple consecutive invalid characters' => [
                'file///name\\\\with::invalid**chars.txt',
                '-',
                // 'file///name\\\\' part will be removed since only the filename part is kept
                'with--invalid--chars.txt'
            ],
            'filename with long extension' => [
                'archive.longestextension',
                '_',
                'archive.longestext'
            ],
            'very long filename' => [
                $veryLongFileName,
                '_',
                $veryLongSanitizedFileName
            ],
            'invalid filename with empty replacement' => [
                '???.txt',
                ' ',
                ' .txt'
            ]
        ];
    }


    /**
     * Test sanitizeFilename method with various filenames.
     *
     * @param non-empty-string $fileName Filename to sanitize
     * @param non-empty-string $replacement Character to use as replacement for invalid characters
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('sanitizeFilenameDataProvider')]
    final public function testSanitizeFilename(string $fileName, string $replacement, string $expected): void
    {
        $this->assertEquals($expected, File::sanitizeFilename($fileName, $replacement));
    }


    /**
     * Test 'dirSize' method.
     *
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface|UnexpectedValueException on failure
     */
    final public function testDirSize(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Test dir size (10 files 10 bytes each = 100 bytes)
        $this->assertEquals(100, File::dirSize($testDir));
        File::removeDirectory($testDir);
    }


    /**
     * Test 'dirSize' method by passing a non-existing dir as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|RuntimeException|UnexpectedValueException on failure
     */
    final public function testDirSizeThrowsExceptionOnNonExistingDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::dirSize('test123');
    }


    /**
     * Test 'countFiles' method.
     *
     * @return void
     * @throws InvalidArgumentException|RuntimeException|UnexpectedValueException|SafeExceptionInterface on failure
     */
    final public function testCountFiles(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Test count files (0 files in the root dir and 10 files total)
        $this->assertEquals(0, File::countFiles($testDir));
        $this->assertEquals(10, File::countFiles($testDir, true));
        File::removeDirectory($testDir);
    }


    /**
     * Test 'countFiles' method by passing a non-existing dir as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|RuntimeException|UnexpectedValueException on failure
     */
    final public function testCountFilesThrowsExceptionOnNonExistingDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::countFiles('test123');
    }


    /**
     * Test 'countSubDirs' method.
     *
     * @return void
     * @throws InvalidArgumentException|RuntimeException|SafeExceptionInterface|UnexpectedValueException on failure
     */
    final public function testCountSubDirs(): void
    {
        // Create a test dir
        $testDir = $this->getRandomDirPath();
        $this->createTestDir($testDir);

        // Test count sub-dirs (3 sub-dirs in the root dir and 7 sub-dirs total)
        $this->assertEquals(3, File::countSubDirs($testDir));
        $this->assertEquals(7, File::countSubDirs($testDir, true));
        File::removeDirectory($testDir);
    }


    /**
     * Test 'countSubDirs' method by passing a non-existing dir as a parameter.
     *
     * @return void
     * @throws InvalidArgumentException|RuntimeException|UnexpectedValueException on failure
     */
    final public function testCountSubDirsThrowsExceptionOnNonExistingDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::countSubDirs('test123');
    }
}
