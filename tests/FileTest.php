<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\File;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\SafeExceptionInterface;

/**
 * Tests for File helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-14
 * Time: 15:39
 */
class FileTest extends TestCase
{
    /**
     * @var non-empty-string $testBaseDir Base directory for tests
     */
    private string $testBaseDir = 'temp';

    /**
     * @var string $testNestedDir Nested directory path for tests
     */
    private string $testNestedDir = 'nested' . DIRECTORY_SEPARATOR . 'dir';


    /**
     * @inheritDoc
     */
    final protected function setUp(): void
    {
        // Try to create a writable test directory
        $tempDir = sys_get_temp_dir();
        if (!is_dir($tempDir)) {
            $this->markTestSkipped('System temp directory does not exist');
        }

        if (!is_writable($tempDir)) {
            $this->markTestSkipped('System temp directory is not writable');
        }

        // Create our own test directory
        $this->testBaseDir = $tempDir . '/phpunit_tests_' . uniqid('', true);

        try {
            \Safe\mkdir($this->testBaseDir, 0755, true);
            if (!is_dir($this->testBaseDir)) {
                $this->markTestSkipped('Could not create test directory');
            }
        } catch (SafeExceptionInterface $exception) {
            $this->markTestSkipped('Could not create test directory: ' . $exception->getMessage());
        }
    }


    /**
     * @inheritDoc
     */
    final protected function tearDown(): void
    {
        if (isset($this->testBaseDir) && is_dir($this->testBaseDir)) {
            try {
                File::removeDirectory($this->testBaseDir);
            } catch (InvalidArgumentException|SafeExceptionInterface $exception) {
                $this->markTestIncomplete('Could not remove test directory: ' . $exception->getMessage());
            }
        }
    }


    /**
     * Test fileExtension method with various paths.
     * @return void
     */
    final public function testFileExtension(): void
    {
        $this->assertEquals('', File::fileExtension('binfile'));
        $this->assertEquals('', File::fileExtension('.htaccess'));
        $this->assertEquals('txt', File::fileExtension('document.txt'));
        $this->assertEquals('tar.gz', File::fileExtension('archive.tar.gz'));
        $this->assertEquals('tar.bz2', File::fileExtension('ftp://server.tech/archive.tar.bz2'));
        $this->assertEquals('tar.gz', File::fileExtension('C:\\path\\to\\file.tar.gz'));
    }


    /**
     * Test fileName method with various paths.
     * @return void
     */
    final public function testFileName(): void
    {
        $this->assertEquals('binfile', File::fileName('binfile'));
        $this->assertEquals('binfile', File::fileName('binfile', false));

        $this->assertEquals('.htaccess', File::fileName('.htaccess'));
        $this->assertEquals('.htaccess', File::fileName('.htaccess', false));

        $this->assertEquals('document.txt', File::fileName('document.txt'));
        $this->assertEquals('document', File::fileName('document.txt', false));

        $this->assertEquals('archive.tar.gz', File::fileName('archive.tar.gz'));
        $this->assertEquals('archive', File::fileName('archive.tar.gz', false));

        $this->assertEquals('archive.tar.bz2', File::fileName('ftp://server.tech/archive.tar.bz2'));
        $this->assertEquals('archive', File::fileName(
            'ftp://server.tech/archive.tar.bz2',
            false
        ));

        $this->assertEquals('file.tar.gz', File::fileName("C:\\path\\to\\file.tar.gz"));
        $this->assertEquals('file', File::fileName("C:\\path\\to\\file.tar.gz", false));

        $this->assertEquals('file.tar.gz', File::fileName("C:/path/to/file.tar.gz"));
        $this->assertEquals('file', File::fileName("C:/path/to/file.tar.gz", false));
    }


    /**
     * Test pathIsAbsolute method with various paths.
     * @return void
     * @throws SafeExceptionInterface
     */
    final public function testIsAbsolutePath(): void
    {
        $this->assertTrue(File::isAbsolutePath('/var/www/html'));
        $this->assertTrue(File::isAbsolutePath('C:\\Program Files\\App'));
        $this->assertTrue(File::isAbsolutePath('C:/Program Files/App'));
        $this->assertFalse(File::isAbsolutePath('relative/path/to/file'));
        $this->assertFalse(File::isAbsolutePath('another\\relative\\path'));
    }


    /**
     * Create the test directory.
     * @param non-empty-string $dir Directory path
     * @param int $mode Directory permissions
     * @return void
     */
    private function createTestDir(string $dir, int $mode = 0775): void
    {
        try {
            File::ensureDirectory($dir, $mode);
        } catch (InvalidArgumentException|SafeExceptionInterface $exception) {
            $this->markTestIncomplete('Could not create test directory: ' . $exception->getMessage());
        }
    }


    /**
     * Remove the test directory.
     * @param non-empty-string $dir Directory path
     * @param bool $recursively Whether to remove directories recursively
     * @return void
     */
    private function removeTestDir(string $dir, bool $recursively = false): void
    {
        try {
            if ($recursively) {
                File::removeDirectory($dir);
            } else {
                \Safe\rmdir($dir);
            }
        } catch (InvalidArgumentException|SafeExceptionInterface $exception) {
            $this->markTestIncomplete('Could not remove test directory: ' . $exception->getMessage());
        }
    }


    /**
     * Test ensureDirectory method by creating temporary directories.
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testEnsureDirectory(): void
    {
        // Define temporary directory path
        $nestedDir = $this->testBaseDir . DIRECTORY_SEPARATOR . $this->testNestedDir;

        // Ensure the directory does not exist
        if (is_dir($nestedDir)) {
            $this->removeTestDir($nestedDir, true);
        }

        // Test creating nested directories with default permissions and owner/group
        $this->createTestDir($nestedDir);
        $this->assertDirectoryExists($nestedDir);

        // Test ensuring an already existing directory
        $this->createTestDir($nestedDir);
        $this->removeTestDir($nestedDir);

        // Test creating directory with specific permissions
        $this->createTestDir($nestedDir, 0750);
        $this->assertDirectoryExists($nestedDir);

        try {
            $filePerms = substr(sprintf('%o', \Safe\fileperms($nestedDir)), -3);
        } catch (SafeExceptionInterface) {
            $this->markTestIncomplete('Could not get directory permissions');
        }

        $this->assertEquals('750', $filePerms);
        $this->removeTestDir($nestedDir);

        // Test creating non-absolute directory path
        $this->expectException(InvalidArgumentException::class);
        File::ensureDirectory('nested/dir');

        // Test creating invalid directory path
        // $this->expectException(Throwable::class);
        // File::ensureDirectory($this->testBaseDir . DIRECTORY_SEPARATOR . "\0invalid");
    }


    /**
     * Test removeDirectory method with various cases.
     * @return void
     * @throws InvalidArgumentException|SafeExceptionInterface on failure
     */
    final public function testRemoveDirectory(): void
    {
        // Create a test nested dir if not exists
        $nestedDir = $this->testBaseDir . DIRECTORY_SEPARATOR . $this->testNestedDir;
        if (!is_dir($nestedDir)) {
            $this->createTestDir($nestedDir);
        }

        // Remove non-existing directory
        $this->expectException(InvalidArgumentException::class);
        File::removeDirectory($nestedDir . '_non_existing');

        // Remove empty directory
        File::removeDirectory($nestedDir);
        $this->assertDirectoryDoesNotExist($nestedDir);

        // Create a test nested dir with files and subdirectories
        try {
            $this->createTestDir($nestedDir);
            \Safe\file_put_contents($nestedDir . DIRECTORY_SEPARATOR . 'file1.txt', 'Test file 1');
            $this->createTestDir($nestedDir . DIRECTORY_SEPARATOR . 'subdir1');
            \Safe\file_put_contents(
                $nestedDir . DIRECTORY_SEPARATOR . 'subdir1' . DIRECTORY_SEPARATOR . 'file2.txt',
                'Test file 2'
            );
        } catch(SafeExceptionInterface $exception) {
            $this->markTestIncomplete('Could not create test directory: ' . $exception->getMessage());
        }

        // Remove non-empty directory recursively
        File::removeDirectory($nestedDir);
        $this->assertDirectoryDoesNotExist($nestedDir);
    }


    /**
     * Test sanitizeFilename method with various filenames.
     * @return void
     */
    final public function testSanitizeFilename(): void
    {
        $this->assertEquals('valid_filename.txt', File::sanitizeFilename('valid_filename.txt'));
        $this->assertEquals(
            '!!  invalid ¹²% _файлнаме__.txt',
            File::sanitizeFilename(' !! 🤬 invalid ¹²% _файлнаме??.txt ')
        );

        $this->assertEquals('aux_', File::sanitizeFilename('aux'));
        $this->assertEquals('__', File::sanitizeFilename('??'));
    }
}
