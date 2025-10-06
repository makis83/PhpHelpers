<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\Html;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for Html helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-15
 * Time: 15:49
 */
#[CoversClass(Html::class)]
class HtmlTest extends TestCase
{
    /**
     * Data provider for 'testIsHTML' method.
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function isHTMLDataProvider(): array
    {
        return [
            'simple text' => ['This is a simple text.', false],
            'simple text with HTML-like characters' => ['This is a <simple> text & more.', true],
            'simple HTML' => ['<p>This is a simple paragraph.</p>', true],
            'complex HTML' => ['<div><h1>Title</h1><p>Paragraph with <a href="#">link</a>.</p></div>', true],
            'empty string' => ['   ', false],
            'string with only HTML tags' => ['<br><hr><img src="image.jpg" />', true]
        ];
    }


    /**
     * Test 'isHtml' method.
     *
     * @param string $text Text to check
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isHTMLDataProvider')]
    final public function testIsHTML(string $text, bool $expected): void
    {
        $this->assertEquals($expected, Html::isHTML($text));
    }


    /**
     * Data provider for 'testSecure' method.
     *
     * @return array<string, array{0: string, 1: bool, 2: bool, 3: string}>
     */
    public static function secureDataProvider(): array
    {
        return [
            'empty string' => ['   ', true, true, ''],
            'simple text' => ['This is a simple text.', true, true, 'This is a simple text.'],
            'simple text with HTML-like entities (forcing HTML)' => [
                'This is a "simple" text with entities & smth else™.',
                true,
                true,
                'This is a &quot;simple&quot; text with entities &amp; smth else&trade;.'
            ],
            'simple text with HTML-like entities (not forcing HTML)' => [
                'This is a &quot;simple" text with entities & smth else&trade;.',
                false,
                true,
                'This is a "simple" text with entities & smth else™.'
            ],
            'HTML with malicious code with purification' => [
                '<p>This is a <b>simple</a> paragraph.</p><img src="javascript:evil();" onload="evil();" />',
                true,
                true,
                '&lt;p&gt;This is a &lt;b&gt;simple paragraph.&lt;/b&gt;&lt;/p&gt;'
            ],
            'HTML with malicious code without purification' => [
                '<p>This is a <b>simple</a> paragraph.</p><img src="javascript:evil();" onload="evil();" />',
                true,
                false,
                '&lt;p&gt;This is a &lt;b&gt;simple&lt;/a&gt; paragraph.&lt;/p&gt;&lt;img src=&quot;javascript:evil();&quot; onload=&quot;evil();&quot; /&gt;'
            ]
        ];
    }


    /**
     * Test 'secure' method.
     *
     * @param string $data Simple text or HTML code
     * @param bool $forceHtml Whether to force HTML output even if text is not an HTML
     * @param bool $purify Whether to purify the HTML (makes the code safer and standard-compliant)
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('secureDataProvider')]
    final public function testSecure(string $data, bool $forceHtml, bool $purify, string $expected): void
    {
        $this->assertEquals($expected, Html::secure($data, $forceHtml, $purify));
    }


    /**
     * Data provider for 'testTidy' method.
     *
     * @return array<string, array{0: string, 1: bool, 2: string}>
     */
    public static function tidyDataProvider(): array
    {
        return [
            'empty string' => ['   ', true, ''],
            'simple text' => ['This is a simple text.', true, 'This is a simple text.'],
            'simple text with HTML-like entities' => [
                'This is a "simple" text with entities & smth else™.',
                true,
                'This is a &quot;simple&quot; text with entities &amp; smth else&trade;.'
            ],
            'HTML with correct structure' => [
                '<p>This is a <b>simple</b> paragraph.</p>',
                true,
                "<p>This is a <strong>simple</strong> paragraph.</p>\n"
            ],
            'HTML with correct structure (no repair)' => array(
                '<p>This is a <b>simple</b> paragraph.</p>',
                false,
                "<p>This is a <b>simple</b> paragraph.</p>\n"
            ),
            'HTML with incorrect structure' => [
                '<p class="aaa" class="bbb">This is a <b>simple</a> paragraph.</p>',
                true,
                "<p class=\"aaa bbb\">This is a <strong>simple paragraph.</strong></p>\n"
            ],
            'HTML with incorrect structure (no repair)' => [
                '<p class="aaa" class="bbb">This is a <b>simple</a> paragraph.</p>',
                false,
                "<p class=\"aaa bbb\">This is a <b>simple paragraph.</b></p>\n"
            ],
            'HTML with malicious code' => [
                '<p>This is a <b>simple</a> paragraph.</p><img src="javascript:evil();" onload="evil();" />',
                true,
                "<p>This is a <strong>simple paragraph.</strong></p>\n<strong><img src=\"javascript:evil();\" onload=\"evil();\" /></strong>"
            ],
            'HTML with malicious code (no repair)' => [
                '<p>This is a <b>simple</a> paragraph.</p><img src="javascript:evil();" onload="evil();" />',
                false,
                "<p>This is a <b>simple paragraph.</b></p>\n<b><img src=\"javascript:evil();\" onload=\"evil();\" /></b>"
            ]
        ];
    }


    /**
     * Test 'tidy' method.
     *
     * @param string $html HTML code
     * @param bool $repair Whether to repair the possibly broken HTML
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('tidyDataProvider')]
    final public function testTidy(string $html, bool $repair, string $expected): void
    {
        $this->assertEquals($expected, Html::tidy($html, $repair));
    }


    /**
     * Data provider for 'testTextToHtml' method.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function textToHtmlDataProvider(): array
    {
        return [
            'empty string' => ['   ', ''],
            'HTML content' => [
                '<h2>Warning!</h2><div>This is a <strong>simple</strong> paragraph.</div>',
                '<h2>Warning!</h2><div>This is a <strong>simple</strong> paragraph.</div>'
            ],
            'simple text without line breaks' => [
                'This is a simple text without line breaks.',
                "<p>This is a simple text without line breaks.</p>\n"
            ],
            'simple text with single line breaks' => [
                "This is a simple text\nwith single line breaks.",
                "<p>This is a simple text<br />\nwith single line breaks.</p>\n"
            ],
            'simple text with multiple paragraphs' => [
                "This is the first paragraph.\n\nThis is the second paragraph, with a line break.\nSee?\n\nThis is the third paragraph.",
                "<p>This is the first paragraph.</p>\n<p>This is the second paragraph, with a line break.<br />\nSee?</p>\n<p>This is the third paragraph.</p>\n"
            ]
        ];
    }


    /**
     * Test 'textToHtml' method.
     *
     * @param string $text Plain text
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('textToHtmlDataProvider')]
    final public function testTextToHtml(string $text, string $expected): void
    {
        $this->assertEquals($expected, Html::textToHtml($text));
    }


    /**
     * Data provider for 'testGetTextTags' method.
     *
     * @return array<string, array{0: string, 1: string[]}>
     */
    public static function getTextTagsDataProvider(): array
    {
        return [
            'empty string' => ['   ', []],
            'simple text' => ['This is a simple text.', []],
            'simple HTML' => ['<p>This is a <b>simple</b> paragraph.</p>', ['p', 'b']],
            'complex HTML' => [
                '<div><h1>Title</h1><p>Paragraph with <a href="#">link</a> and <span style="color:red;">styled text</span>.<img src="image.jpg" /></p></div>',
                ['div', 'h1', 'p', 'a', 'span', 'img']
            ],
            'HTML with attributes and self-closing tags' => [
                '<br><hr><img src="image.jpg" /><input type="text" /><form action="#"></form>',
                ['br', 'hr', 'img', 'input', 'form']
            ]
        ];
    }


    /**
     * Test 'getTextTags' method.
     *
     * @param string $text Text
     * @param string[] $expected Expected result
     * @return void
     */
    #[DataProvider('getTextTagsDataProvider')]
    final public function testGetTextTags(string $text, array $expected): void
    {
        $this->assertEquals($expected, Html::textTags($text));
    }
}
