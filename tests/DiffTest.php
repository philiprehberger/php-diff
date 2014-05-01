<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff\Tests;

use PhilipRehberger\Diff\ArrayDiff;
use PhilipRehberger\Diff\Change;
use PhilipRehberger\Diff\Diff;
use PhilipRehberger\Diff\DiffLine;
use PhilipRehberger\Diff\DiffStats;
use PhilipRehberger\Diff\ObjectDiff;
use PhilipRehberger\Diff\PropertyChange;
use PhilipRehberger\Diff\StringDiff;
use PHPUnit\Framework\TestCase;

final class DiffTest extends TestCase
{
    public function test_strings_returns_string_diff(): void
    {
        $result = Diff::strings("hello\nworld", "hello\nphp");

        $this->assertInstanceOf(StringDiff::class, $result);
    }

    public function test_identical_strings_have_no_changes(): void
    {
        $result = Diff::strings('hello', 'hello');

        $this->assertFalse($result->hasChanges());
    }

    public function test_string_diff_detects_changes(): void
    {
        $result = Diff::strings("line1\nline2\nline3", "line1\nchanged\nline3");

        $this->assertTrue($result->hasChanges());
    }

    public function test_string_diff_stats(): void
    {
        $result = Diff::strings("a\nb\nc", "a\nx\nc");

        $stats = $result->stats();

        $this->assertInstanceOf(DiffStats::class, $stats);
        $this->assertSame(1, $stats->added);
        $this->assertSame(1, $stats->removed);
        $this->assertSame(2, $stats->unchanged);
    }

    public function test_string_diff_to_array_returns_diff_lines(): void
    {
        $result = Diff::strings("a\nb", "a\nc");

        $lines = $result->toArray();

        $this->assertNotEmpty($lines);
        $this->assertContainsOnlyInstancesOf(DiffLine::class, $lines);
    }

    public function test_string_diff_unified_output(): void
    {
        $result = Diff::strings("a\nb\nc", "a\nx\nc");

        $unified = $result->toUnified();

        $this->assertStringContainsString('@@', $unified);
        $this->assertTrue(
            str_contains($unified, '-b') || str_contains($unified, '+x') || str_contains($unified, '-a') || str_contains($unified, '+a'),
            'Unified output should contain diff markers'
        );
    }

    public function test_string_diff_html_output(): void
    {
        $result = Diff::strings('hello', 'world');

        $html = $result->toHtml();

        $this->assertStringContainsString('<div class="diff">', $html);
        $this->assertStringContainsString('<del class="diff-removed">', $html);
        $this->assertStringContainsString('<ins class="diff-added">', $html);
    }

    public function test_arrays_returns_array_diff(): void
    {
        $result = Diff::arrays(['a' => 1], ['a' => 2]);

        $this->assertInstanceOf(ArrayDiff::class, $result);
    }

    public function test_array_diff_detects_added(): void
    {
        $result = Diff::arrays([], ['key' => 'value']);

        $added = $result->added();

        $this->assertCount(1, $added);
        $this->assertSame('key', $added[0]->key);
        $this->assertSame('value', $added[0]->new);
        $this->assertSame('added', $added[0]->type);
    }

    public function test_array_diff_detects_removed(): void
    {
        $result = Diff::arrays(['key' => 'value'], []);

        $removed = $result->removed();

        $this->assertCount(1, $removed);
        $this->assertSame('key', $removed[0]->key);
        $this->assertSame('value', $removed[0]->old);
        $this->assertSame('removed', $removed[0]->type);
    }

    public function test_array_diff_detects_changed(): void
    {
        $result = Diff::arrays(['key' => 'old'], ['key' => 'new']);

        $changed = $result->changed();

        $this->assertCount(1, $changed);
        $this->assertInstanceOf(Change::class, $changed[0]);
        $this->assertSame('old', $changed[0]->old);
        $this->assertSame('new', $changed[0]->new);
    }

    public function test_identical_arrays_have_no_changes(): void
    {
        $result = Diff::arrays(['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]);

        $this->assertFalse($result->hasChanges());
        $this->assertEmpty($result->changes());
    }

    public function test_objects_returns_object_diff(): void
    {
        $old = (object) ['name' => 'Alice', 'age' => 30];
        $new = (object) ['name' => 'Alice', 'age' => 31];

        $result = Diff::objects($old, $new);

        $this->assertInstanceOf(ObjectDiff::class, $result);
        $this->assertTrue($result->hasChanges());

        $changes = $result->changes();
        $this->assertCount(1, $changes);
        $this->assertInstanceOf(PropertyChange::class, $changes[0]);
        $this->assertSame('age', $changes[0]->property);
        $this->assertSame(30, $changes[0]->from);
        $this->assertSame(31, $changes[0]->to);
    }

    public function test_identical_objects_have_no_changes(): void
    {
        $obj = (object) ['name' => 'Alice'];

        $result = Diff::objects($obj, clone $obj);

        $this->assertFalse($result->hasChanges());
    }
}
