[![CI](https://github.com/makis83/PhpHelpers/actions/workflows/ci.yml/badge.svg)](https://github.com/makis83/PhpHelpers/actions/workflows/ci.yml)
[![PHPStan](https://github.com/makis83/PhpHelpers/actions/workflows/phpstan.yml/badge.svg)](https://github.com/makis83/PhpHelpers/actions/workflows/phpstan.yml)

Makis83 PHP Helpers
===================

A collection of PHP helper classes for files, texts, images etc.

## Installation

Use composer to install Makis83 PHP Helpers:

```bash
composer require makis83/helpers
```

## Requirements

* PHP 8.2 or higher
* Mbstring extension for working with text
* Tidy extension for working with HTML code

## Documentation

### Data Helper

#### `isJson(string $text): bool`
Check if the specified text is JSON.

```php
// Valid JSON
$isJson = Data::isJson('{"key": "value"}'); // true
$isJson = Data::isJson('["item1", "item2"]'); // true
$isJson = Data::isJson('123'); // true

// Invalid JSON
$isJson = Data::isJson('This is not JSON.'); // false
$isJson = Data::isJson('{"key": "value"'); // false
```

#### `jsonEncode(array $data, int $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP): string`
Encode array as JSON.

```php
// Simple array
$json = Data::jsonEncode(['item1', 'item2', 'item3']);
// Result: ["item1","item2","item3"]

// Associative array
$json = Data::jsonEncode(['key' => 'value']);
// Result: {"key":"value"}

// Nested array
$json = Data::jsonEncode(['user' => ['name' => 'John', 'age' => 30]]);
// Result: {"user":{"name":"John","age":30}}
```

#### `jsonDecode(string $data, bool $asArray = true): mixed`
Decode the JSON-sequence.

```php
// Simple array
$array = Data::jsonDecode('["item1","item2","item3"]');
// Result: ['item1', 'item2', 'item3']

// Associative array
$array = Data::jsonDecode('{"key":"value"}');
// Result: ['key' => 'value']

// Nested array
$array = Data::jsonDecode('{"user":{"name":"John","age":30}}');
// Result: ['user' => ['name' => 'John', 'age' => 30]]
```

#### `valueToArray(array|null|bool|int|float|string $value, string $delimiter = ','): array`
Convert value into array.

```php
// Single value
$array = Data::valueToArray('singleValue');
// Result: ['singleValue']

// Comma-separated values
$array = Data::valueToArray('value1, value2, value3');
// Result: ['value1', 'value2', 'value3']

// Array input (returns as-is)
$array = Data::valueToArray(['arrayValue1', 'arrayValue2']);
// Result: ['arrayValue1', 'arrayValue2']
```

### File Helper

#### `fileExtension(string $path): string`
Get file extension from a file with the given path.

```php
$extension = File::fileExtension('document.txt'); // Returns: "txt"
$extension = File::fileExtension('archive.tar.gz'); // Returns: "tar.gz"
$extension = File::fileExtension('ftp://server.tech/archive.tar.bz2'); // Returns: "tar.bz2"
```

#### `fileName(string $path, bool $withExtension = true): string`
Get file name from the given path.

```php
$name = File::fileName('document.txt'); // Returns: "document.txt"
$name = File::fileName('document.txt', false); // Returns: "document"
$name = File::fileName('ftp://server.tech/archive.tar.bz2'); // Returns: "archive.tar.bz2"
$name = File::fileName('ftp://server.tech/archive.tar.bz2', false); // Returns: "archive"
```

#### `isAbsolutePath(string $path, bool $allowSchemes = true): bool`
Check if the specified path is an absolute path.

```php
$isAbsolute = File::isAbsolutePath('/var/www/html'); // true (Unix)
$isAbsolute = File::isAbsolutePath('C:\\Program Files\\App'); // true (Windows)
$isAbsolute = File::isAbsolutePath('relative/path/to/file'); // false
```

#### `ensureDirectory(string $path, int $mode = 0775, ?string $owner = null, ?string $group = null): true`
Create a directory with the given path if it doesn't exist.

```php
// Create a directory (will create all parent directories if needed)
File::ensureDirectory('/var/www/html/myapp/cache');
```

#### `removeDirectory(string $path): void`
Remove directory and all its files and subdirs recursively.

```php
// Remove a directory and all its contents
File::removeDirectory('/var/www/html/myapp/cache');
```

#### `sanitizeFilename(string $fileName, string $replacement = '_'): string`
Sanitizes a filename by removing characters that are problematic for filesystems.

```php
$sanitized = File::sanitizeFilename(' !! 🤬 invalid ¹²% _файлнаме??.txt ');
// Returns: "!!  invalid ¹²% _файлнаме__.txt"

$sanitized = File::sanitizeFilename('aux');
// Returns: "aux_"
```

### Html Helper

#### `isHTML(string $text): bool`
Detect whether the text is an HTML code.

```php
$isHtml = Html::isHTML('This is a simple text.'); // false
$isHtml = Html::isHTML('<p>This is a simple paragraph.</p>'); // true
$isHtml = Html::isHTML('This is a <simple> text & more.'); // true
```

#### `secure(string $data, bool $forceHtml = true, bool $purify = true, ?string $charset = null): string`
Secure the specified text data.

```php
// Simple text
$secured = Html::secure('This is a simple text.');
// Result: "This is a simple text."

// Text with HTML-like entities
$secured = Html::secure('This is a "simple" text with entities & smth else™.');
// Result: "This is a "simple" text with entities & smth else&trade;."

// HTML with purification
$secured = Html::secure('<p>This is a <b>simple</a> paragraph.</p><img src="javascript:evil();" onload="evil();" />');
// Result: "<p>This is a <b>simple paragraph.</b></p>"
```

#### `tidy(string $html, bool $repair = true, ?string $charset = null): string`
Tidy the specified HTML code.

```php
// Simple text
$tidied = Html::tidy('This is a simple text.');
// Result: "This is a simple text."

// HTML with correct structure
$tidied = Html::tidy('<p>This is a <b>simple</b> paragraph.</p>');
// Result: "<p>This is a <strong>simple</strong> paragraph.</p>\n"

// HTML with incorrect structure
$tidied = Html::tidy('<p class="aaa" class="bbb">This is a <b>simple</a> paragraph.</p>');
// Result: "<p class=\"aaa bbb\">This is a <strong>simple paragraph.</strong></p>\n"
```

#### `textToHtml(string $text, ?string $charset = null): string`
Convert a plain text into HTML.

```php
// Simple text without line breaks
$html = Html::textToHtml('This is a simple text without line breaks.');
// Result: "<p>This is a simple text without line breaks.</p>\n"

// Text with line breaks
$html = Html::textToHtml("This is a simple text\nwith single line breaks.");
// Result: "<p>This is a simple text<br />\nwith single line breaks.</p>\n"

// Multiple paragraphs
$html = Html::textToHtml("This is the first paragraph.\n\nThis is the second paragraph, with a line break.\nSee?\n\nThis is the third paragraph.");
// Result: "<p>This is the first paragraph.</p>\n<p>This is the second paragraph, with a line break.<br />\nSee?</p>\n<p>This is the third paragraph.</p>\n"
```

#### `textTags(string $text): array`
Get HTML tags used in the text.

```php
// Simple text
$tags = Html::textTags('This is a simple text.');
// Result: []

// Simple HTML
$tags = Html::textTags('<p>This is a <b>simple</b> paragraph.</p>');
// Result: ['p', 'b']

// Complex HTML
$tags = Html::textTags('<div><h1>Title</h1><p>Paragraph with <a href="#">link</a> and <span style="color:red;">styled text</span>.<img src="image.jpg" /></p></div>');
// Result: ['div', 'h1', 'p', 'a', 'span', 'img']
```

### Text Helper

#### `setLeadingZeroes(int $number, int $numberLength = 2): string`
Prepend the integer value with leading zeroes.

```php
$zeroPadded = Text::setLeadingZeroes(5); // Returns: "05"
$zeroPadded = Text::setLeadingZeroes(5, 3); // Returns: "005"
$zeroPadded = Text::setLeadingZeroes(123, 3); // Returns: "123"
$zeroPadded = Text::setLeadingZeroes(1234, 3); // Returns: "1234"
```

#### `classNameToId(string $class): string`
Convert class name (with or without namespace) to ID.

```php
$id = Text::classNameToId('MyClassName'); // Returns: "my-class-name"
$id = Text::classNameToId('App\\Models\\MyClassName'); // Returns: "my-class-name"
$id = Text::classNameToId('ABC'); // Returns: "a-b-c"
$id = Text::classNameToId('already-id'); // Returns: "already-id"
```

#### `fixSpaces(string $text): string`
Fixes issues with spaces, NBSPs etc.

```php
// Basic usage
$fixed = Text::fixSpaces('This is a test.'); // Returns: "This is a test."

// With NBSPs
$fixed = Text::fixSpaces("This is a test."); // Returns: "This is a test."
$fixed = Text::fixSpaces("This \xC2\xA0 is \xC2\xA0 a \xC2\xA0 test."); // Returns: "This is a test."

// With narrow NBSPs
$fixed = Text::fixSpaces("This \xE2\x80\xAF is \xE2\x80\xAF a \xE2\x80\xAF test."); // Returns: "This is a test."

// With RLM characters
$fixed = Text::fixSpaces("This \xE2\x80\x8F is \xE2\x80\x8F\xe2\x80\x8a a \xE2\x80\x8F test.\xe2\x80\x83"); // Returns: "This is a test."
```

## Tests
Run the tests using PHPUnit and PHPStan:

```bash
./composer test
```

or

```bash
./vendor/bin/phpunit --coverage-html coverage/html
./vendor/bin/phpstan analyse
```

## License

This project is licensed under the [MIT License](https://opensource.org/license/mit).
