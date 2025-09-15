<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\Text;
use PHPUnit\Framework\TestCase;

/**
 * Class description.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-15
 * Time: 18:55
 */
class TextTest extends TestCase
{
    /**
     * Test 'setLeadingZeroes' method.
     * @return void
     */
    final public function testSetLeadingZeroes(): void
    {
        $this->assertEquals('05', Text::setLeadingZeroes(5));
        $this->assertEquals('005', Text::setLeadingZeroes(5, 3));
        $this->assertEquals('123', Text::setLeadingZeroes(123, 3));
        $this->assertEquals('1234', Text::setLeadingZeroes(1234, 3));
        $this->assertEquals('0', Text::setLeadingZeroes(0, 0));
    }


    /**
     * Test 'classNameToId' method.
     * @return void
     */
    final public function testClassNameToId(): void
    {
        $this->assertEquals('my-class-name', Text::classNameToId('MyClassName'));
        $this->assertEquals('my-class-name', Text::classNameToId('App\\Models\\MyClassName'));
        $this->assertEquals('a', Text::classNameToId('A'));
        $this->assertEquals('a-b-c', Text::classNameToId('ABC'));
        $this->assertEquals('already-id', Text::classNameToId('already-id'));
    }


    /**
     * Test 'fixSpaces' method.
     * @return void
     */
    final public function testFixSpaces(): void
    {
        $this->assertEquals('', Text::fixSpaces('   '));
        $this->assertEquals('This is a test.', Text::fixSpaces('This is a test.'));

        // With NBSPs
        $this->assertEquals('This is a test.', Text::fixSpaces("This is a test."));

        // With NBSPs
        $this->assertEquals(
            'This is a test.',
            Text::fixSpaces("This \xC2\xA0 is \xC2\xA0 a \xC2\xA0 test.")
        );

        // With narrow NBSPs
        $this->assertEquals(
            'This is a test.',
            Text::fixSpaces("This \xE2\x80\xAF is \xE2\x80\xAF a \xE2\x80\xAF test.")
        );

        // With RLM characters
        $this->assertEquals(
            'This is a test.',
            Text::fixSpaces("This \xE2\x80\x8F is \xE2\x80\x8F\xe2\x80\x8a a \xE2\x80\x8F test.\xe2\x80\x83")
        );
    }
}
