<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff\Tests;

use PhilipRehberger\Diff\Diff;
use PHPUnit\Framework\TestCase;

final class AnsiOutputTest extends TestCase
{
    public function test_ansi_output_contains_red_for_removed_lines(): void
    {
        $diff = Diff::strings('old', 'new');

        $ansi = $diff->toAnsi();

        $this->assertStringContainsString("\033[31m", $ansi);
    }

    public function test_ansi_output_contains_green_for_added_lines(): void
    {
        $diff = Diff::strings('old', 'new');

        $ansi = $diff->toAnsi();

        $this->assertStringContainsString("\033[32m", $ansi);
    }

    public function test_ansi_output_contains_cyan_for_hunk_headers(): void
    {
        $diff = Diff::strings('old', 'new');

        $ansi = $diff->toAnsi();

        $this->assertStringContainsString("\033[36m", $ansi);
    }

    public function test_ansi_output_contains_reset_codes(): void
    {
        $diff = Diff::strings('old', 'new');

        $ansi = $diff->toAnsi();

        $this->assertStringContainsString("\033[0m", $ansi);
    }

    public function test_ansi_output_returns_empty_for_identical_strings(): void
    {
        $diff = Diff::strings('same', 'same');

        $this->assertSame('', $diff->toAnsi());
    }

    public function test_ansi_output_respects_context_parameter(): void
    {
        $lines = implode("\n", range(1, 20));
        $newLines = explode("\n", $lines);
        $newLines[10] = 'changed';
        $modified = implode("\n", $newLines);

        $smallContext = Diff::strings($lines, $modified)->toAnsi(1);
        $largeContext = Diff::strings($lines, $modified)->toAnsi(5);

        $this->assertNotEmpty($smallContext);
        $this->assertNotEmpty($largeContext);
        $this->assertGreaterThan(
            substr_count($smallContext, "\n"),
            substr_count($largeContext, "\n"),
        );
    }

    public function test_ansi_output_colors_each_line_type_correctly(): void
    {
        $diff = Diff::strings("keep\nold", "keep\nnew");

        $ansi = $diff->toAnsi();
        $lines = explode("\n", $ansi);

        $hasRed = false;
        $hasGreen = false;
        $hasCyan = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, "\033[31m")) {
                $hasRed = true;
                $this->assertStringEndsWith("\033[0m", $line);
            }
            if (str_starts_with($line, "\033[32m")) {
                $hasGreen = true;
                $this->assertStringEndsWith("\033[0m", $line);
            }
            if (str_starts_with($line, "\033[36m")) {
                $hasCyan = true;
                $this->assertStringEndsWith("\033[0m", $line);
            }
        }

        $this->assertTrue($hasRed, 'Should have red-colored removed lines');
        $this->assertTrue($hasGreen, 'Should have green-colored added lines');
        $this->assertTrue($hasCyan, 'Should have cyan-colored hunk headers');
    }
}
