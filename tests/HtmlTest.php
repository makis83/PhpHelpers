<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\Html;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Html helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-15
 * Time: 15:49
 */
class HtmlTest extends TestCase
{
    /**
     * Test 'isHtml' method.
     * @return void
     */
    final public function testIsHTML(): void
    {
        // Simple text
        $this->assertFalse(Html::isHTML('This is a simple text.'));

        // Simple text with HTML-like characters
        $this->assertTrue(Html::isHTML('This is a <simple> text & more.'));

        // Simple HTML
        $this->assertTrue(Html::isHTML('<p>This is a simple paragraph.</p>'));

        // Complex HTML
        $this->assertTrue(Html::isHTML('<div><h1>Title</h1><p>Paragraph with <a href="#">link</a>.</p></div>'));

        // Empty string
        $this->assertFalse(Html::isHTML('   '));

        // String with only HTML tags
        $this->assertTrue(Html::isHTML('<br><hr><img src="image.jpg" />'));
    }


    /**
     * Test 'secure' method.
     * @return void
     */
    final public function testSecure(): void
    {
        // Empty string
        $this->assertEquals(
            '',
            Html::secure('   ')
        );

        // Simple text
        $this->assertEquals(
            'This is a simple text.',
            Html::secure('This is a simple text.')
        );

        // Simple text with HTML-like entities
        $this->assertEquals(
            'This is a &quot;simple&quot; text with entities &amp; smth else&trade;.',
            Html::secure('This is a "simple" text with entities & smth else™.')
        );

        // Simple text with HTML-like entities, but not forcing HTML
        $this->assertEquals(
            'This is a "simple" text with entities & smth else™.',
            Html::secure('This is a &quot;simple" text with entities & smth else&trade;.', false)
        );

        // Simple HTML with purification
        $this->assertEquals(
            '&lt;p&gt;This is a &lt;b&gt;simple paragraph.&lt;/b&gt;&lt;/p&gt;',
            Html::secure('<p>This is a <b>simple</a> paragraph.</p><img src="javascript:evil();" onload="evil();" />')
        );

        // Simple HTML without purification
        $this->assertEquals(
            '&lt;p&gt;This is a &lt;b&gt;simple&lt;/a&gt; paragraph.&lt;/p&gt;&lt;img src=&quot;javascript:evil();&quot; onload=&quot;evil();&quot; /&gt;',
            Html::secure('<p>This is a <b>simple</a> paragraph.</p><img src="javascript:evil();" onload="evil();" />', purify: false)
        );
    }


    /**
     * Test 'secure' method.
     * @return void
     */
    final public function testTidy(): void
    {
        // Empty string
        $this->assertEquals(
            '',
            Html::tidy('   ')
        );

        // Simple text
        $this->assertEquals(
            'This is a simple text.',
            Html::tidy('This is a simple text.')
        );

        // Simple text with HTML-like entities
        $this->assertEquals(
            'This is a &quot;simple&quot; text with entities &amp; smth else&trade;.',
            Html::tidy('This is a "simple" text with entities & smth else™.')
        );

        // HTML with correct structure
        $this->assertEquals(
            "<p>This is a <strong>simple</strong> paragraph.</p>\n",
            Html::tidy('<p>This is a <b>simple</b> paragraph.</p>')
        );

        // HTML with incorrect structure
        $this->assertEquals(
            "<p class=\"aaa bbb\">This is a <strong>simple paragraph.</strong></p>\n",
            Html::tidy('<p class="aaa" class="bbb">This is a <b>simple</a> paragraph.</p>')
        );
    }


    /**
     * Test 'secure' method.
     * @return void
     */
    final public function testTextToHtml(): void
    {
        // Empty string
        $this->assertEquals(
            '',
            Html::textToHtml('   ')
        );

        // HTML content
        $this->assertEquals(
            "<h2>Warning!</h2\n><div>This is a <strong>simple</strong> paragraph.</div>",
            Html::textToHtml("<h2>Warning!</h2\n><div>This is a <strong>simple</strong> paragraph.</div>")
        );

        // Simple text without line breaks
        $this->assertEquals(
            "<p>This is a simple text without line breaks.</p>\n",
            Html::textToHtml('This is a simple text without line breaks.')
        );

        // Simple text with single line breaks
        $this->assertEquals(
            "<p>This is a simple text<br />\nwith single line breaks.</p>\n",
            Html::textToHtml("This is a simple text\nwith single line breaks.")
        );

        // Simple text with multiple paragraphs
        $this->assertEquals(
            "<p>This is the first paragraph.</p>\n<p>This is the second paragraph, with a line break.<br />\nSee?</p>\n<p>This is the third paragraph.</p>\n",
            Html::textToHtml("This is the first paragraph.\n\nThis is the second paragraph, with a line break.\nSee?\n\nThis is the third paragraph.")
        );
    }


    final public function testGetTextTags(): void
    {
        // Empty string
        $this->assertEquals(
            [],
            Html::textTags('   ')
        );

        // Simple text
        $this->assertEquals(
            [],
            Html::textTags('This is a simple text.')
        );

        // Simple HTML
        $this->assertEquals(
            ['p', 'b'],
            Html::textTags('<p>This is a <b>simple</b> paragraph.</p>')
        );

        // Complex HTML
        $this->assertEquals(
            ['div', 'h1', 'p', 'a', 'span', 'img'],
            Html::textTags('<div><h1>Title</h1><p>Paragraph with <a href="#">link</a> and <span style="color:red;">styled text</span>.<img src="image.jpg" /></p></div>')
        );

        // HTML with attributes and self-closing tags
        $this->assertEquals(
            ['br', 'hr', 'img', 'input', 'form'],
            Html::textTags('<br><hr><img src="image.jpg" /><input type="text" /><form action="#"></form>')
        );
    }
}
