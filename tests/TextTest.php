<?php

namespace Makis83\Helpers\Tests;

use Makis83\Helpers\Text;
use Makis83\Helpers\Html;
use Random\RandomException;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\IconvException;
use Safe\Exceptions\SafeExceptionInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for Text helper.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-15
 * Time: 18:55
 */
#[CoversClass(Text::class)]
#[UsesClass(Html::class)]
class TextTest extends TestCase
{
    /**
     * Data provider for 'testSetLeadingZeroes' method.
     *
     * @return array<string, array{0: int, 1: non-negative-int, 2: string}>
     */
    public static function setLeadingZeroesDataProvider(): array
    {
        return [
            'single digit with default length' => [5, 2, '05'],
            'single digit with length 3' => [5, 3, '005'],
            'three digits with length 3' => [123, 3, '123'],
            'four digits with length 3' => [1234, 3, '1234'],
            'zero with length 0' => [0, 0, '0']
        ];
    }


    /**
     * Test 'setLeadingZeroes' method.
     *
     * @param int $number Number
     * @param non-negative-int $numberLength Total length of the number including leading zeroes
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('setLeadingZeroesDataProvider')]
    final public function testSetLeadingZeroes(int $number, int $numberLength, string $expected): void
    {
        $this->assertEquals($expected, Text::setLeadingZeroes($number, $numberLength));
    }


    /**
     * Data provider for 'testClassNameToId' method.
     *
     * @return array<string, array{0: non-empty-string, 1: string}>
     */
    public static function classNameToIdDataProvider(): array
    {
        return [
            'simple class name' => ['MyClassName', 'my-class-name'],
            'namespaced class name' => ['App\\Models\\MyClassName', 'my-class-name'],
            'single letter class name' => ['A', 'a'],
            'acronym class name' => ['ABC', 'a-b-c'],
            'already id' => ['already-id', 'already-id']
        ];
    }


    /**
     * Test 'classNameToId' method.
     *
     * @param non-empty-string $class Class' full path
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('classNameToIdDataProvider')]
    final public function testClassNameToId(string $class, string $expected): void
    {
        $this->assertEquals($expected, Text::classNameToId($class));
    }


    /**
     * Data provider for 'testFixSpaces' method.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function fixSpacesDataProvider(): array
    {
        return [
            'empty string' => ['   ', ''],
            'simple text' => ['This is a test.', 'This is a test.'],
            'with NBSPs' => ["This is a test.", 'This is a test.'],
            'with various spaces' => ["This \xC2\xA0 is \xC2\xA0 a \xC2\xA0 test.", 'This is a test.'],
            'with narrow NBSPs' => ["This \xE2\x80\xAF is \xE2\x80\xAF a \xE2\x80\xAF test.", 'This is a test.'],
            'with RLM characters' => [
                "This \xE2\x80\x8F is \xE2\x80\x8F\xe2\x80\x8a a \xE2\x80\x8F test.\xe2\x80\x83",
                'This is a test.'
            ]
        ];
    }


    /**
     * Test 'fixSpaces' method.
     *
     * @param string $text Text to be fixed
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('fixSpacesDataProvider')]
    final public function testFixSpaces(string $text, string $expected): void
    {
        $this->assertEquals($expected, Text::fixSpaces($text));
    }


    /**
     * Test 'random' method.
     *
     * @return void
     * @throws RandomException
     */
    final public function testRandom(): void
    {
        // Test default settings
        $randomString = Text::random();
        $this->assertEquals(10, mb_strlen($randomString));

        // Test different lengths
        $length = 5;
        $randomString = Text::random($length);
        $this->assertEquals($length, mb_strlen($randomString));

        $length = 225;
        $randomString = Text::random($length);
        $this->assertEquals($length, mb_strlen($randomString));

        // Test characters collection
        $randomString = Text::random();
        $this->assertTrue(ctype_alnum($randomString));

        $randomString = Text::random(collection: 'alpha');
        $this->assertTrue(ctype_alpha($randomString));

        $randomString = Text::random(collection: 'numeric');
        $this->assertTrue(ctype_digit($randomString));

        // Test characters case
        $randomString = Text::random(case: 'lower');
        $this->assertMatchesRegularExpression('/[0-9a-z]{10}/', $randomString);

        $randomString = Text::random(case: 'upper');
        $this->assertMatchesRegularExpression('/[0-9A-Z]{10}/', $randomString);

        $randomString = Text::random(collection: 'alpha', case: 'lower');
        $this->assertTrue(ctype_lower($randomString));

        $randomString = Text::random(collection: 'alpha', case: 'upper');
        $this->assertTrue(ctype_upper($randomString));
    }


    /**
     * Data provider for 'testIsAscii' method.
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function isAsciiDataProvider(): array
    {
        return [
            'empty string' => ['   ', true],
            'ASCII text' => ['This is a test.', true],
            'text with Russian letters' => ['This is a тест.', false],
            'text with Unicode characters' => ['This is a test with Unicode characters: ä, ö, ü, ß', false],
            'ASCII text with NBSPs' => ["This is a test.", false]
        ];
    }


    /**
     * Test 'isAscii' method.
     *
     * @param string $string String to be checked
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isAsciiDataProvider')]
    final public function testIsAscii(string $string, bool $expected): void
    {
        $this->assertEquals($expected, Text::isAscii($string));
    }


    /**
     * Data provider for 'testIsUnicode' method.
     *
     * @return array<string, array{0: string, 1: bool}>
     * @throws IconvException on conversion error
     */
    public static function isUnicodeDataProvider(): array
    {
        return [
            'empty string' => ['   ', true],
            'ASCII text' => ['This is a test.', true],
            'text with Russian letters' => ['This is a тест.', true],
            'text with Unicode characters' => ['This is a test with Unicode characters: ä, ö, ü, ß', true],
            'ASCII text with NBSPs' => ["This is a test.", true],
            'text encoded as KOI8-R from Windows-1251' => [
                \Safe\iconv('Windows-1251', 'KOI8-R//IGNORE', 'Вопрос'),
                false
            ]
        ];
    }


    /**
     * Test 'testIsUnicode' method.
     *
     * @param string $string Text to be checked
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isUnicodeDataProvider')]
    final public function testIsUnicode(string $string, bool $expected): void
    {
        $this->assertEquals($expected, Text::isUnicode($string));
    }


    /**
     * Data provider for 'testIsPunycode' method.
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function isPunycodeDataProvider(): array
    {
        return [
            'empty string' => ['   ', false],
            'ASCII text' => ['This is a test.', false],
            'text with Unicode characters' => ['This is a test with Unicode characters: ä, ö, ü, ß', false],
            'punycode text' => ['xn--this is a test with unicode characters: , , , -5xd7uu6afj', true],
            'invalid punycode text' => ['xn--this is a test with unicode characters: , , , 5xd7uu6afj', false]
        ];
    }


    /**
     * Test 'testIsPunycode' method.
     *
     * @param string $string Text to be checked
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('isPunycodeDataProvider')]
    final public function testIsPunycode(string $string, bool $expected): void
    {
        $this->assertEquals($expected, Text::isPunycode($string));
    }


    /**
     * Data provider for 'testTransliterate' method.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function transliterateDataProvider(): array
    {
        return [
            'empty string' => ['', ''],
            'string with spaces only' => ['   ', ''],
            'ASCII text' => ['This is a test.', 'This is a test.'],
            'text with Cyrillic letters' => ['This is a тест.', 'This is a test.'],
            'text with Cyrillic letter only' => ['Это тест.', 'Eto test.']
        ];
    }


    /**
     * Test 'transliterate' method.
     *
     * @param string $string String to transliterate
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('transliterateDataProvider')]
    final public function testTransliterate(string $string, string $expected): void
    {
        $this->assertEquals($expected, Text::transliterate($string));
    }


    /**
     * Data provider for 'testSlug' method.
     *
     * @return array<string, array{0: string, 1: bool, 2: bool, 3: string, 4: positive-int, 5: string}>
     */
    public static function slugDataProvider(): array
    {
        return [
            'empty string' => ['', false, false, '-', 255, ''],
            'string with different spaces' => [
                " This\xE2\x80\x8F\xe2\x80\x8ais a test. ",
                false,
                false,
                '-',
                255,
                'this-is-a-test'
            ],
            'string with underscores' => [
                'This_is a_test.',
                false,
                false,
                '-',
                255,
                'this-is-a-test'
            ],
            'string with non default delimiters' => [
                'This_is a test.',
                false,
                false,
                '_',
                255,
                'this_is_a_test'
            ],
            'string with punctuation' => [
                'Is "this" — a test?',
                false,
                false,
                '-',
                255,
                'is-this-a-test'
            ],
            'ASCII text' => ['This is a test.', false, false, '-', 255, 'this-is-a-test'],
            'ASCII text with uppercase option' => ['This is a test.', true, false, '-', 255, 'This-is-a-test'],
            'ASCII text with length limit' => ['This is a test.', false, false, '-', 5, 'this'],
            'cyrillic text' => ['Это тест.', false, false, '-', 255, 'eto-test'],
            'cyrillic text with uppercase option' => ['Это тест.', true, false, '-', 255, 'Eto-test'],
            'cyrillic text with length limit' => ['Это тест.', false, false, '-', 5, 'eto-t'],
            'cyrillic text with unicode support' => ['Это тест.', false, true, '-', 255, 'это-тест'],
            'cyrillic text with unicode support and uppercase option' => [
                'Это тест.',
                true,
                true,
                '-',
                255,
                'Это-тест'
            ],
            'cyrillic text with unicode support and length limit' => ['Это тест.', false, true, '-', 5, 'это-т'],
        ];
    }


    /**
     * Test 'slug' method.
     *
     * @param string $string String to generate alias from
     * @param bool $allowUppercase Allow uppercase characters in the alias
     * @param bool $allowUnicode Allow Unicode characters in the alias
     * @param string $delimiter Delimiter
     * @param positive-int $length Max length
     * @param string $expected Expected result
     * @return void
     * @throws SafeExceptionInterface
     */
    #[DataProvider('slugDataProvider')]
    final public function testSlug(
        string $string,
        bool $allowUppercase,
        bool $allowUnicode,
        string $delimiter,
        int $length,
        string $expected
    ): void {
        $this->assertEquals($expected, Text::slug($string, $allowUppercase, $allowUnicode, $delimiter, $length));
    }


    /**
     * Data provider for 'testHasNewLine' method.
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function hasNewLineDataProvider(): array
    {
        return [
            'empty string' => ['', false],
            'string with no new lines' => ['This is a test.', false],
            'string with new lines' => ["This is a test.\nSecond line here.", true],
        ];
    }


    /**
     * Test 'hasNewLine' method.
     *
     * @param string $string String to check
     * @param bool $expected Expected result
     * @return void
     */
    #[DataProvider('hasNewLineDataProvider')]
    final public function testHasNewLine(string $string, bool $expected): void
    {
        $this->assertEquals($expected, Text::hasNewLine($string));
    }


    /**
     * Data provider for 'testLtrim' method.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function ltrimDataProvider(): array
    {
        return [
            'empty string' => ['', 'prefix', ''],
            'empty prefix' => ['string', '', 'string'],
            'empty string and prefix' => ['', '', ''],
            'string with prefix' => ['prefixString', 'prefix', 'String'],
            'string without prefix' => ['string', 'prefix', 'string'],
            'string equals prefix' => ['prefix', 'prefix', ''],
            'string with partial prefix match' => ['preString', 'prefix', 'preString'],
            'string with unicode prefix' => ['тестString', 'тест', 'String'],
            'string with spaces' => ['  spaced string', ' ', ' spaced string'],
            'string with special characters' => ['###test', '###', 'test']
        ];
    }


    /**
     * Test 'ltrim' method.
     *
     * @param string $string Original string
     * @param string $remove Part to be removed
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('ltrimDataProvider')]
    final public function testLtrim(string $string, string $remove, string $expected): void
    {
        $this->assertEquals($expected, Text::ltrim($string, $remove));
    }


    /**
     * Data provider for 'testRtrim' method.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function rtrimDataProvider(): array
    {
        return [
            'empty string' => ['', 'suffix', ''],
            'empty suffix' => ['string', '', 'string'],
            'empty string and suffix' => ['', '', ''],
            'string with suffix' => ['StringSuffix', 'Suffix', 'String'],
            'string without suffix' => ['string', 'suffix', 'string'],
            'string equals suffix' => ['suffix', 'suffix', ''],
            'string with partial suffix match' => ['StringSuff', 'Suffix', 'StringSuff'],
            'string with unicode suffix' => ['Stringтест', 'тест', 'String'],
            'string with spaces' => ['string spaced ', ' ', 'string spaced'],
            'string with special characters' => ['test###', '###', 'test'],
        ];
    }


    /**
     * Test 'rtrim' method.
     *
     * @param string $string Original string
     * @param string $remove Part to be removed
     * @param string $expected Expected result
     * @return void
     */
    #[DataProvider('rtrimDataProvider')]
    final public function testRtrim(string $string, string $remove, string $expected): void
    {
        $this->assertEquals($expected, Text::rtrim($string, $remove));
    }


    /**
     * Data provider for 'testIntro' method.
     *
     * @return array<string, array{0: string, 1: int, 2: null|string, 3: bool, 4: string}>
     */
    public static function introDataProvider(): array
    {
        $text = "Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.\nA small river named Duden flows by their place and supplies it with the necessary regelialia. It is a paradisematic country, in which roasted parts of sentences fly into your mouth.\nEven the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.";
        $htmlText = "<p>Far far away, behind the word mountains, far from the countries <strong>Vokalia</strong> and <strong>Consonantia</strong>, there live the blind texts. Separated they live in <i>Bookmarksgrove</i> right at the coast of the Semantics, a large language ocean.</p>\n<p>A small river named <span class='active'>Duden</span> flows by their place and supplies it with the necessary regelialia. It is a paradisematic country, in which roasted parts of sentences fly into your mouth.</p>\n<p>Even the all-powerful Pointing has no control about the blind texts it is an almost unorthographic life One day however a small line of blind text by the name of Lorem Ipsum decided to leave for the far World of Grammar.</p>";

        return [
            'empty string' => ['', 10, null, false, ''],
            'empty html text' => ['<p>   </p>', 10, null, false, ''],
            'short text' => ['text', 20, null, false, 'text'],
            'regular text' => [
                $text,
                100,
                null,
                false,
                'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the…'
            ],
            'regular text with custom trailing' => [
                $text,
                100,
                '&rarr;',
                false,
                'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the&rarr;'
            ],
            'regular text without trailing dots' => [
                $text,
                100,
                '',
                false,
                'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the'
            ],
            'regular text with big length' => [
                $text,
                300,
                null,
                false,
                'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean. A small river named Duden flows by their place and supplies it with the necessary regelialia…'
            ],
            'regular text with big length, but using only the first line' => [
                $text,
                300,
                null,
                true,
                'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.'
            ],
            'regular HTML text with big length' => [
                $htmlText,
                300,
                null,
                false,
                'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean. A small river named Duden flows by their place and supplies it with the necessary regelialia…'
            ],
        ];
    }


    /**
     * Test 'intro' method.
     *
     * @param string $text Full text
     * @param int $length Intro length (chars)
     * @param null|string $trailingChars Trailing characters to add to the intro
     * @param bool $useFirstLineOnly Whether to use only first line of text
     * @param string $expected Expected result
     * @return void
     * @throws SafeExceptionInterface
     */
    #[DataProvider('introDataProvider')]
    final public function testIntro(
        string $text,
        int $length,
        null|string $trailingChars,
        bool $useFirstLineOnly,
        string $expected
    ): void {
        $this->assertEquals($expected, Text::intro($text, $length, $trailingChars, $useFirstLineOnly));
    }


    /**
     * Test 'split' method with short text.
     *
     * @return void
     * @throws SafeExceptionInterface
     */
    final public function testSplitWithShortText(): void
    {
        // Text with about 300 characters
        $maxPartLength = 300;
        $text = "One morning, when Gregor Samsa woke from troubled dreams, he found himself transformed in his bed into a horrible vermin. He lay on his armour-like back, and if he lifted his head a little he could see his brown belly, slightly domed and divided by arches into stiff sections. The bedding was hardly.";
        $textParts = Text::split($text, $maxPartLength);
        $this->assertCount(1, $textParts);
        $this->assertLessThanOrEqual($maxPartLength, mb_strlen($textParts[0]));
    }


    /**
     * Test 'split' method with short HTML text.
     *
     * @return void
     * @throws SafeExceptionInterface
     */
    final public function testSplitWithShortHtmlText(): void
    {
        // Text with about 300 characters
        $maxPartLength = 300;
        $text = "<p>The quick, <b>brown</b> fox jumps over a lazy dog.</p>\n<p>DJs flock by when MTV ax quiz prog.</p>\n<p>Junk MTV quiz graced by fox whelps.</p>\n<p>Bawds jog, flick quartz, vex nymphs.</p>\n<p>Waltz, bad nymph, for quick jigs vex! Fox <span class='alert'>nymphs</span> grab quick-jived waltz.</p>";
        $textParts = Text::split($text, $maxPartLength);
        $this->assertCount(1, $textParts);
        $this->assertLessThanOrEqual($maxPartLength, mb_strlen($textParts[0]));
    }


    /**
     * Test 'split' method with long text.
     *
     * @return void
     * @throws SafeExceptionInterface
     */
    final public function testSplitWithLongText(): void
    {
        // Text with about 500 characters
        $maxPartLength = 300;
        $text = "A wonderful serenity has taken possession of my entire soul, like these sweet mornings of spring which I enjoy with my whole heart. I am alone, and feel the charm of existence in this spot, which was created for the bliss of souls like mine. I am so happy, my dear friend, so absorbed in the exquisite sense of mere tranquil existence, that I neglect my talents. I should be incapable of drawing a single stroke at the present moment; and yet I feel that I never was a greater artist than now.";
        $textParts = Text::split($text, $maxPartLength);
        $this->assertCount(2, $textParts);
        foreach ($textParts as $part) {
            $this->assertLessThanOrEqual($maxPartLength, mb_strlen($part));
        }
    }


    /**
     * Test 'split' method with long HTML text.
     *
     * @return void
     * @throws SafeExceptionInterface
     */
    final public function testSplitWithLongHtmlText(): void
    {
        // Text with about 670 characters
        $maxPartLength = 300;
        $text = "<p>The European <strong>languages</strong> are members of the same family.</p>\n<p>Their separate existence is a myth.</p>\n<p>For <span class='example'>science</span>, <span class='example'>music</span>, <span class='example'>sport</span>, etc, Europe uses the same vocabulary.</p>\n<p>The languages only differ in their <em>grammar</em>, their pronunciation and their most common words.</p>\n<p>Everyone <strong>realizes</strong> why a new common language would be desirable: one could refuse to pay expensive translators.</p>\n<p>To achieve this, <span class='text-danger'>it would be necessary</span> to have uniform grammar, pronunciation and more common words.</p>";
        $textParts = Text::split($text, $maxPartLength);
        $this->assertCount(3, $textParts);
        foreach ($textParts as $part) {
            $this->assertLessThanOrEqual($maxPartLength, mb_strlen($part));
        }
    }


    /**
     * Data provider for 'testRemoveCorruptedChars' method.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function removeCorruptedCharsDataProvider(): array
    {
        return [
            'empty string' => ['', ''],
            'ASCII string' => ['This is a test.', 'This is a test.'],
            'Unicode string' => ['Это тест.', 'Это тест.'],
            'ASCII string with corrupted characters' => ['This is a t��est.�', 'This is a test.'],
            'Unicode string with corrupted characters' => ['Это т�ест.�', 'Это тест.'],
        ];
    }


    /**
     * Test 'removeCorruptedChars' method.
     *
     * @param string $string String to remove corrupted characters from
     * @param string $expected Expected result
     * @return void
     * @throws SafeExceptionInterface
     */
    #[DataProvider('removeCorruptedCharsDataProvider')]
    final public function testRemoveCorruptedChars(string $string, string $expected): void
    {
        $this->assertEquals($expected, Text::removeCorruptedChars($string));
    }
}
