<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff\Tests;

use PhilipRehberger\Diff\Diff;
use PhilipRehberger\Diff\DiffLine;
use PhilipRehberger\Diff\PropertyChange;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Unified output format
    // -------------------------------------------------------------------------

    public function test_unified_output_contains_hunk_header(): void
    {
        $result = Diff::strings("a\nb\nc", "a\nx\nc");

        $unified = $result->toUnified();

        $this->assertMatchesRegularExpression('/@@ -\d+,\d+ \+\d+,\d+ @@/', $unified);
    }

    public function test_unified_output_marks_removed_lines_with_minus(): void
    {
        $result = Diff::strings('before', 'after');

        $unified = $result->toUnified();

        $this->assertStringContainsString('-before', $unified);
    }

    public function test_unified_output_marks_added_lines_with_plus(): void
    {
        $result = Diff::strings('before', 'after');

        $unified = $result->toUnified();

        $this->assertStringContainsString('+after', $unified);
    }

    public function test_unified_output_context_lines_prefixed_with_space(): void
    {
        $result = Diff::strings("hello\nworld", "hello\nearth");

        $unified = $result->toUnified();

        // Verify context lines exist with space prefix
        $this->assertMatchesRegularExpression('/^ /m', $unified);
    }

    public function test_unified_output_with_custom_context_size(): void
    {
        $lines = implode("\n", range(1, 20));
        $newLines = explode("\n", $lines);
        $newLines[10] = 'changed';
        $modified = implode("\n", $newLines);

        $resultSmall = Diff::strings($lines, $modified)->toUnified(1);
        $resultLarge = Diff::strings($lines, $modified)->toUnified(5);

        $this->assertNotEmpty($resultSmall);
        $this->assertNotEmpty($resultLarge);
        $this->assertGreaterThan(
            substr_count($resultSmall, "\n"),
            substr_count($resultLarge, "\n"),
        );
    }

    public function test_unified_output_returns_empty_for_identical_strings(): void
    {
        $result = Diff::strings('hello', 'hello');

        $this->assertSame('', $result->toUnified());
    }

    public function test_unified_output_returns_empty_for_empty_strings(): void
    {
        $result = Diff::strings('', '');

        $this->assertSame('', $result->toUnified());
    }

    // -------------------------------------------------------------------------
    // HTML output format
    // -------------------------------------------------------------------------

    public function test_html_output_wraps_in_diff_div(): void
    {
        $result = Diff::strings('a', 'b');

        $html = $result->toHtml();

        $this->assertStringStartsWith('<div class="diff">', $html);
        $this->assertStringEndsWith('</div>', $html);
    }

    public function test_html_output_uses_ins_for_added(): void
    {
        $result = Diff::strings('', 'new line');

        $html = $result->toHtml();

        $this->assertStringContainsString('<ins class="diff-added">', $html);
        $this->assertStringContainsString('</ins>', $html);
    }

    public function test_html_output_uses_del_for_removed(): void
    {
        $result = Diff::strings('old line', '');

        $html = $result->toHtml();

        $this->assertStringContainsString('<del class="diff-removed">', $html);
        $this->assertStringContainsString('</del>', $html);
    }

    public function test_html_output_uses_span_for_unchanged(): void
    {
        $result = Diff::strings("keep\nchange", "keep\nnew");

        $html = $result->toHtml();

        $this->assertStringContainsString('<span class="diff-unchanged">', $html);
    }

    public function test_html_output_escapes_special_characters(): void
    {
        $result = Diff::strings('<script>alert("xss")</script>', '<b>safe</b>');

        $html = $result->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_html_output_for_identical_strings_shows_unchanged(): void
    {
        $result = Diff::strings('same', 'same');

        $html = $result->toHtml();

        $this->assertStringContainsString('<span class="diff-unchanged">', $html);
        $this->assertStringNotContainsString('<ins', $html);
        $this->assertStringNotContainsString('<del', $html);
    }

    public function test_html_output_for_empty_strings(): void
    {
        $result = Diff::strings('', '');

        $html = $result->toHtml();

        $this->assertSame('<div class="diff"></div>', $html);
    }

    // -------------------------------------------------------------------------
    // Array (DiffLine) output format
    // -------------------------------------------------------------------------

    public function test_to_array_returns_diff_line_objects_with_line_numbers(): void
    {
        $result = Diff::strings("a\nb\nc", "a\nx\nc");

        $lines = $result->toArray();

        foreach ($lines as $index => $line) {
            $this->assertInstanceOf(DiffLine::class, $line);
            $this->assertSame($index + 1, $line->lineNumber);
        }
    }

    public function test_to_array_contains_all_change_types(): void
    {
        $result = Diff::strings("a\nb\nc", "a\nx\nc");

        $lines = $result->toArray();
        $types = array_map(fn (DiffLine $l): string => $l->type, $lines);

        $this->assertContains('unchanged', $types);
        $this->assertContains('added', $types);
        $this->assertContains('removed', $types);
    }

    public function test_to_array_for_identical_strings_all_unchanged(): void
    {
        $result = Diff::strings("a\nb", "a\nb");

        $lines = $result->toArray();

        foreach ($lines as $line) {
            $this->assertSame('unchanged', $line->type);
        }
    }

    public function test_to_array_for_empty_strings_returns_empty(): void
    {
        $result = Diff::strings('', '');

        $this->assertSame([], $result->toArray());
    }

    // -------------------------------------------------------------------------
    // Stats output
    // -------------------------------------------------------------------------

    public function test_stats_for_empty_strings(): void
    {
        $result = Diff::strings('', '');

        $stats = $result->stats();

        $this->assertSame(0, $stats->added);
        $this->assertSame(0, $stats->removed);
        $this->assertSame(0, $stats->unchanged);
    }

    public function test_stats_for_identical_strings(): void
    {
        $result = Diff::strings("a\nb\nc", "a\nb\nc");

        $stats = $result->stats();

        $this->assertSame(0, $stats->added);
        $this->assertSame(0, $stats->removed);
        $this->assertSame(3, $stats->unchanged);
    }

    public function test_stats_for_completely_different_strings(): void
    {
        $result = Diff::strings('old', 'new');

        $stats = $result->stats();

        $this->assertSame(1, $stats->added);
        $this->assertSame(1, $stats->removed);
        $this->assertSame(0, $stats->unchanged);
    }

    public function test_stats_for_addition_only(): void
    {
        $result = Diff::strings('', 'new');

        $stats = $result->stats();

        $this->assertSame(1, $stats->added);
        $this->assertSame(0, $stats->removed);
    }

    public function test_stats_for_removal_only(): void
    {
        $result = Diff::strings('old', '');

        $stats = $result->stats();

        $this->assertSame(0, $stats->added);
        $this->assertSame(1, $stats->removed);
    }

    // -------------------------------------------------------------------------
    // Edge cases: empty and identical inputs
    // -------------------------------------------------------------------------

    public function test_empty_old_to_non_empty_new(): void
    {
        $result = Diff::strings('', "a\nb\nc");

        $this->assertTrue($result->hasChanges());

        $stats = $result->stats();
        $this->assertSame(3, $stats->added);
        $this->assertSame(0, $stats->removed);
    }

    public function test_non_empty_old_to_empty_new(): void
    {
        $result = Diff::strings("a\nb\nc", '');

        $this->assertTrue($result->hasChanges());

        $stats = $result->stats();
        $this->assertSame(0, $stats->added);
        $this->assertGreaterThan(0, $stats->removed);
    }

    public function test_single_line_change(): void
    {
        $result = Diff::strings('before', 'after');

        $this->assertTrue($result->hasChanges());

        $unified = $result->toUnified();
        $this->assertStringContainsString('-before', $unified);
        $this->assertStringContainsString('+after', $unified);
    }

    public function test_multiline_identical_has_no_changes(): void
    {
        $text = "line1\nline2\nline3\nline4\nline5";

        $result = Diff::strings($text, $text);

        $this->assertFalse($result->hasChanges());
        $this->assertSame('', $result->toUnified());
    }

    // -------------------------------------------------------------------------
    // Large diff
    // -------------------------------------------------------------------------

    public function test_large_diff_produces_valid_output(): void
    {
        $oldLines = [];
        $newLines = [];

        for ($i = 0; $i < 100; $i++) {
            $oldLines[] = "line {$i}";
            $newLines[] = $i % 10 === 0 ? "changed {$i}" : "line {$i}";
        }

        $result = Diff::strings(implode("\n", $oldLines), implode("\n", $newLines));

        $this->assertTrue($result->hasChanges());

        $unified = $result->toUnified();
        $this->assertStringContainsString('@@', $unified);

        $stats = $result->stats();
        $this->assertGreaterThan(0, $stats->added);
        $this->assertGreaterThan(0, $stats->removed);
        $this->assertSame($stats->added, $stats->removed);
        $this->assertGreaterThan(0, $stats->unchanged);
    }

    // -------------------------------------------------------------------------
    // Object diff with nested objects
    // -------------------------------------------------------------------------

    public function test_object_diff_detects_nested_object_change(): void
    {
        $old = (object) [
            'name' => 'Alice',
            'address' => (object) ['city' => 'Vienna', 'zip' => '1010'],
        ];

        $new = (object) [
            'name' => 'Alice',
            'address' => (object) ['city' => 'Berlin', 'zip' => '10115'],
        ];

        $result = Diff::objects($old, $new);

        $this->assertTrue($result->hasChanges());

        $changes = $result->changes();
        $this->assertCount(1, $changes);
        $this->assertSame('address', $changes[0]->property);
    }

    public function test_object_diff_identical_nested_objects(): void
    {
        $address = (object) ['city' => 'Vienna', 'zip' => '1010'];

        $old = (object) ['name' => 'Alice', 'address' => $address];
        $new = (object) ['name' => 'Alice', 'address' => $address];

        $result = Diff::objects($old, $new);

        $this->assertFalse($result->hasChanges());
    }

    public function test_object_diff_with_added_property(): void
    {
        $old = (object) ['name' => 'Alice'];
        $new = (object) ['name' => 'Alice', 'email' => 'alice@example.com'];

        $result = Diff::objects($old, $new);

        $this->assertTrue($result->hasChanges());

        $changes = $result->changes();
        $this->assertCount(1, $changes);
        $this->assertSame('email', $changes[0]->property);
        $this->assertNull($changes[0]->from);
        $this->assertSame('alice@example.com', $changes[0]->to);
    }

    public function test_object_diff_with_removed_property(): void
    {
        $old = (object) ['name' => 'Alice', 'email' => 'alice@example.com'];
        $new = (object) ['name' => 'Alice'];

        $result = Diff::objects($old, $new);

        $this->assertTrue($result->hasChanges());

        $changes = $result->changes();
        $this->assertCount(1, $changes);
        $this->assertSame('email', $changes[0]->property);
        $this->assertSame('alice@example.com', $changes[0]->from);
        $this->assertNull($changes[0]->to);
    }

    public function test_object_diff_empty_objects(): void
    {
        $result = Diff::objects((object) [], (object) []);

        $this->assertFalse($result->hasChanges());
        $this->assertSame([], $result->changes());
    }

    public function test_object_diff_deeply_nested_objects(): void
    {
        $old = (object) [
            'config' => (object) [
                'db' => (object) ['host' => 'localhost', 'port' => 3306],
                'cache' => true,
            ],
        ];

        $new = (object) [
            'config' => (object) [
                'db' => (object) ['host' => 'localhost', 'port' => 5432],
                'cache' => true,
            ],
        ];

        $result = Diff::objects($old, $new);

        $this->assertTrue($result->hasChanges());

        $changes = $result->changes();
        $this->assertCount(1, $changes);
        $this->assertSame('config', $changes[0]->property);
        $this->assertInstanceOf(PropertyChange::class, $changes[0]);
    }

    public function test_object_diff_multiple_property_changes(): void
    {
        $old = (object) ['a' => 1, 'b' => 2, 'c' => 3];
        $new = (object) ['a' => 10, 'b' => 2, 'c' => 30];

        $result = Diff::objects($old, $new);

        $this->assertTrue($result->hasChanges());
        $this->assertCount(2, $result->changes());

        $properties = array_map(
            fn (PropertyChange $c): string => $c->property,
            $result->changes(),
        );
        $this->assertContains('a', $properties);
        $this->assertContains('c', $properties);
    }

    // -------------------------------------------------------------------------
    // Array diff edge cases
    // -------------------------------------------------------------------------

    public function test_array_diff_empty_arrays(): void
    {
        $result = Diff::arrays([], []);

        $this->assertFalse($result->hasChanges());
        $this->assertSame([], $result->changes());
    }

    public function test_array_diff_numeric_keys(): void
    {
        $result = Diff::arrays([0 => 'a', 1 => 'b'], [0 => 'a', 1 => 'c']);

        $changed = $result->changed();
        $this->assertCount(1, $changed);
        $this->assertSame(1, $changed[0]->key);
    }

    public function test_array_diff_mixed_add_remove_change(): void
    {
        $old = ['keep' => 1, 'remove' => 2, 'change' => 3];
        $new = ['keep' => 1, 'change' => 99, 'add' => 4];

        $result = Diff::arrays($old, $new);

        $this->assertCount(1, $result->added());
        $this->assertCount(1, $result->removed());
        $this->assertCount(1, $result->changed());
    }
}
