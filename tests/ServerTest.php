<?php

namespace Makis83\Helpers\Tests;

use Exception;
use Makis83\Helpers\Server;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for Server helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-25
 * Time: 16:32
 */
#[CoversClass(Server::class)]
class ServerTest extends TestCase
{
    /**
     * Test 'getOs' method.
     *
     * @return void
     */
    final public function testGetOs(): void
    {
        $os = Server::getOs();
        $this->assertNotEmpty($os);
    }


    /**
     * Test 'getEuid' method.
     *
     * @return void
     */
    final public function testGetEuid(): void
    {
        // Skip test on Windows or if POSIX extension is not loaded
        if (!extension_loaded('posix') || (0 === strncasecmp(Server::getOs(), 'WIN', 3))) {
            $this->markTestSkipped('This test is skipped on Windows or if POSIX extension is not loaded.');
        }

        $euid = Server::getEuid();
        $this->assertGreaterThanOrEqual(0, $euid);
    }


    /**
     * Test 'getUserInfoByUid' method.
     *
     * @return void
     * @throws Exception
     */
    final public function testGetUserInfoByUid(): void
    {
        // Skip test on Windows or if POSIX extension is not loaded
        if (!extension_loaded('posix') || (0 === strncasecmp(Server::getOs(), 'WIN', 3))) {
            $this->markTestSkipped('This test is skipped on Windows or if POSIX extension is not loaded.');
        }

        // Get effective user ID
        $euid = Server::getEuid();

        // Test with undefined UID
        $userInfo = Server::getUserInfoByUid();
        $this->assertArrayHasKey('name', $userInfo);
        $this->assertArrayHasKey('passwd', $userInfo);
        $this->assertArrayHasKey('uid', $userInfo);
        $this->assertArrayHasKey('gid', $userInfo);
        $this->assertArrayHasKey('gecos', $userInfo);
        $this->assertArrayHasKey('dir', $userInfo);
        $this->assertArrayHasKey('shell', $userInfo);
        $this->assertSame($euid, $userInfo['uid']);

        // Test with specified UID
        $userInfo = Server::getUserInfoByUid($euid);
        $this->assertArrayHasKey('uid', $userInfo);
        $this->assertSame($euid, $userInfo['uid']);
    }


    /**
     * Test 'getUserInfoByUid' method by passing a wrong UID.
     *
     * @return void
     * @throws Exception on failure
     */
    final public function testGetUserInfoByUidThrowsExceptionOnWrongUid(): void
    {
        $this->expectException(Exception::class);
        Server::getUserInfoByUid(-1);
    }


    /**
     * Test 'getUserNameByUid' method.
     *
     * @return void
     * @throws Exception
     */
    final public function testGetUserNameByUid(): void
    {
        // Skip test on Windows or if POSIX extension is not loaded
        if (!extension_loaded('posix') || (0 === strncasecmp(Server::getOs(), 'WIN', 3))) {
            $this->markTestSkipped('This test is skipped on Windows or if POSIX extension is not loaded.');
        }

        // Get effective user ID
        $euid = Server::getEuid();

        // Test with undefined UID
        $userName = Server::getUserNameByUid();
        $this->assertNotEmpty($userName);

        // Test with specified UID
        $userName = Server::getUserNameByUid($euid);
        $this->assertNotEmpty($userName);
    }


    /**
     * Test 'getUserNameByUid' method by passing a wrong UID.
     *
     * @return void
     * @throws Exception on failure
     */
    final public function testGetUserNameByUidThrowsExceptionOnWrongUid(): void
    {
        $this->expectException(Exception::class);
        Server::getUserNameByUid(-1);
    }
}
