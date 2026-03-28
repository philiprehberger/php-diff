<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff\Tests;

use PhilipRehberger\Diff\Diff;
use PHPUnit\Framework\TestCase;

final class ContextLinesTest extends TestCase
{
    public function test_context_lines_limits_surrounding_lines(): void
    {
        $oldLines = [];
        $newLines = [];

        for ($i = 1; $i <= 20; $i++) {
            $oldLines[] = "line {$i}";
            $newLines[] = $i === 10 ? 'changed' : "line {$i}";
        }

        $result = Diff::strings(implode("\n", $oldLines), implode("\n", $newLines));

        $unified = $result->toUnified(1);

        // With context=1, we should see lines 9, 10 (removed), changed (added), 11
        // but NOT lines far from the change like 1-7 or 13-20
        $this->assertStringNotContainsString(' line 2', $unified);
        $this->assertStringNotContainsString(' line 7', $unified);
        $this->assertStringContainsString(' line 9', $unified);
        $this->assertStringContainsString('-line 10', $unified);
        $this->assertStringContainsString('+changed', $unified);
        $this->assertStringContainsString(' line 11', $unified);
        $this->assertStringNotContainsString(' line 14', $unified);
        $this->assertStringNotContainsString(' line 20', $unified);
    }

    public function test_larger_context_shows_more_lines(): void
    {
        $oldLines = [];
        $newLines = [];

        for ($i = 1; $i <= 20; $i++) {
            $oldLines[] = "line {$i}";
            $newLines[] = $i === 10 ? 'changed' : "line {$i}";
        }

        $result = Diff::strings(implode("\n", $oldLines), implode("\n", $newLines));

        $small = $result->toUnified(1);
        $large = $result->toUnified(5);

        $smallLineCount = substr_count($small, "\n") + 1;
        $largeLineCount = substr_count($large, "\n") + 1;

        $this->assertGreaterThan($smallLineCount, $largeLineCount);
    }

    public function test_context_zero_shows_only_changes(): void
    {
        $oldLines = [];
        $newLines = [];

        for ($i = 1; $i <= 10; $i++) {
            $oldLines[] = "line {$i}";
            $newLines[] = $i === 5 ? 'changed' : "line {$i}";
        }

        $result = Diff::strings(implode("\n", $oldLines), implode("\n", $newLines));

        $unified = $result->toUnified(0);

        // Should only contain the hunk header and the changed lines
        $lines = explode("\n", $unified);
        $nonHeaderLines = array_filter($lines, fn (string $line) => ! str_starts_with($line, '@@'));

        foreach ($nonHeaderLines as $line) {
            $this->assertTrue(
                str_starts_with($line, '+') || str_starts_with($line, '-'),
                "Expected only +/- lines with context=0, got: {$line}"
            );
        }
    }

    public function test_default_context_is_three(): void
    {
        $oldLines = [];
        $newLines = [];

        for ($i = 1; $i <= 20; $i++) {
            $oldLines[] = "line {$i}";
            $newLines[] = $i === 10 ? 'changed' : "line {$i}";
        }

        $result = Diff::strings(implode("\n", $oldLines), implode("\n", $newLines));

        $defaultContext = $result->toUnified();
        $explicitThree = $result->toUnified(3);

        $this->assertSame($defaultContext, $explicitThree);
    }
}
