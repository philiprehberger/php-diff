# PHP Diff

[![Tests](https://github.com/philiprehberger/php-diff/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-diff/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-diff.svg)](https://packagist.org/packages/philiprehberger/php-diff)
[![License](https://img.shields.io/github/license/philiprehberger/php-diff)](LICENSE)

Diff strings, arrays, and objects with unified, HTML, and structured output.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/php-diff
```

## Usage

### Comparing Strings

```php
use PhilipRehberger\Diff\Diff;

$diff = Diff::strings("hello\nworld", "hello\nphp");

$diff->hasChanges();         // true
$diff->toUnified();          // unified diff string
$diff->toHtml();             // HTML with <ins>/<del> tags
$diff->toHtmlSideBySide();   // side-by-side HTML table
$diff->toArray();            // array of DiffLine objects
$diff->stats();              // DiffStats { added: 1, removed: 1, unchanged: 1 }
$diff->similarity();         // 0.333… (0.0 = completely different, 1.0 = identical)
```

### Similarity Score

```php
use PhilipRehberger\Diff\Diff;

$diff = Diff::strings("hello\nworld", "hello\nphp");

$diff->similarity(); // 0.333… — one of three lines is unchanged
```

### Side-by-Side HTML

```php
use PhilipRehberger\Diff\Diff;

$diff = Diff::strings("hello\nworld", "hello\nphp");

$html = $diff->toHtmlSideBySide();
// <table class="diff-table">
//   <tr><td class="diff-left diff-unchanged">hello</td><td class="diff-right diff-unchanged">hello</td></tr>
//   <tr><td class="diff-left diff-removed">world</td><td class="diff-right"></td></tr>
//   <tr><td class="diff-left"></td><td class="diff-right diff-added">php</td></tr>
// </table>
```

### Comparing Arrays

```php
use PhilipRehberger\Diff\Diff;

$diff = Diff::arrays(
    ['name' => 'Alice', 'age' => 30, 'city' => 'NYC'],
    ['name' => 'Alice', 'age' => 31, 'country' => 'US'],
);

$diff->hasChanges();  // true
$diff->changes();     // all Change objects
$diff->added();       // entries only in the new array
$diff->removed();     // entries only in the old array
$diff->changed();     // entries with different values
```

### Comparing Objects

```php
use PhilipRehberger\Diff\Diff;

$old = (object) ['name' => 'Alice', 'age' => 30];
$new = (object) ['name' => 'Alice', 'age' => 31];

$diff = Diff::objects($old, $new);

$diff->hasChanges();  // true
$diff->changes();     // [PropertyChange { property: 'age', from: 30, to: 31 }]
```

## API

### `Diff` (Static Entry Point)

| Method | Returns | Description |
|--------|---------|-------------|
| `Diff::strings(string $old, string $new)` | `StringDiff` | Compare two strings line by line |
| `Diff::arrays(array $old, array $new)` | `ArrayDiff` | Compare two arrays by key |
| `Diff::objects(object $old, object $new)` | `ObjectDiff` | Compare two objects by property |

### `StringDiff`

| Method | Returns | Description |
|--------|---------|-------------|
| `toUnified(int $context = 3)` | `string` | Unified diff format |
| `toHtml()` | `string` | HTML with ins/del tags |
| `toHtmlSideBySide()` | `string` | Side-by-side HTML table |
| `toArray()` | `array<DiffLine>` | Array of DiffLine value objects |
| `hasChanges()` | `bool` | Whether any differences exist |
| `stats()` | `DiffStats` | Count of added, removed, unchanged lines |
| `similarity()` | `float` | Ratio of unchanged to total lines (0.0–1.0) |

### `ArrayDiff`

| Method | Returns | Description |
|--------|---------|-------------|
| `changes()` | `array<Change>` | All changes |
| `added()` | `array<Change>` | Only added entries |
| `removed()` | `array<Change>` | Only removed entries |
| `changed()` | `array<Change>` | Only modified entries |
| `hasChanges()` | `bool` | Whether any differences exist |

### `ObjectDiff`

| Method | Returns | Description |
|--------|---------|-------------|
| `changes()` | `array<PropertyChange>` | All property changes |
| `hasChanges()` | `bool` | Whether any differences exist |

### Value Objects

- **`DiffLine`** — `type` (`added`|`removed`|`unchanged`), `content`, `lineNumber`
- **`Change`** — `key`, `old`, `new`, `type` (`added`|`removed`|`changed`)
- **`PropertyChange`** — `property`, `from`, `to`
- **`DiffStats`** — `added`, `removed`, `unchanged`

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
