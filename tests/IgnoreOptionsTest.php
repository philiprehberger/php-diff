<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff\Tests;

use PhilipRehberger\Diff\Diff;
use PHPUnit\Framework\TestCase;

final class IgnoreOptionsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // ignoreWhitespace
    // -------------------------------------------------------------------------

    public function test_ignore_whitespace_treats_different_whitespace_as_equal(): void
    {
        $diff = Diff::strings('hello  world', 'hello world')->ignoreWhitespace();

        $this->assertFalse($diff->hasChanges());
    }

    public function test_ignore_whitespace_normalizes_leading_and_trailing_spaces(): void
    {
        $diff = Diff::strings('  hello  ', 'hello')->ignoreWhitespace();

        $this->assertFalse($diff->hasChanges());
    }

    public function test_ignore_whitespace_still_detects_real_changes(): void
    {
        $diff = Diff::strings('hello world', 'hello earth')->ignoreWhitespace();

        $this->assertTrue($diff->hasChanges());
    }

    public function test_ignore_whitespace_multiline(): void
    {
        $diff = Diff::strings("hello  world\n  foo  bar", "hello world\nfoo bar")->ignoreWhitespace();

        $this->assertFalse($diff->hasChanges());
    }

    // -------------------------------------------------------------------------
    // ignoreCase
    // -------------------------------------------------------------------------

    public function test_ignore_case_treats_different_casing_as_equal(): void
    {
        $diff = Diff::strings('Hello', 'hello')->ignoreCase();

        $this->assertFalse($diff->hasChanges());
    }

    public function test_ignore_case_multiline(): void
    {
        $diff = Diff::strings("Hello\nWORLD", "hello\nworld")->ignoreCase();

        $this->assertFalse($diff->hasChanges());
    }

    public function test_ignore_case_still_detects_real_changes(): void
    {
        $diff = Diff::strings('Hello', 'World')->ignoreCase();

        $this->assertTrue($diff->hasChanges());
    }

    // -------------------------------------------------------------------------
    // ignoreBlankLines
    // -------------------------------------------------------------------------

    public function test_ignore_blank_lines_treats_different_blank_lines_as_equal(): void
    {
        $diff = Diff::strings("hello\n\nworld", "hello\nworld")->ignoreBlankLines();

        $this->assertFalse($diff->hasChanges());
    }

    public function test_ignore_blank_lines_multiple_blanks(): void
    {
        $diff = Diff::strings("hello\n\n\n\nworld", "hello\nworld")->ignoreBlankLines();

        $this->assertFalse($diff->hasChanges());
    }

    public function test_ignore_blank_lines_still_detects_real_changes(): void
    {
        $diff = Diff::strings("hello\n\nworld", "hello\nearth")->ignoreBlankLines();

        $this->assertTrue($diff->hasChanges());
    }

    public function test_ignore_blank_lines_whitespace_only_lines_treated_as_blank(): void
    {
        $diff = Diff::strings("hello\n   \nworld", "hello\nworld")->ignoreBlankLines();

        $this->assertFalse($diff->hasChanges());
    }

    // -------------------------------------------------------------------------
    // Chaining multiple options
    // -------------------------------------------------------------------------

    public function test_chaining_ignore_case_and_whitespace(): void
    {
        $diff = Diff::strings('  Hello  World  ', 'hello world')->ignoreWhitespace()->ignoreCase();

        $this->assertFalse($diff->hasChanges());
    }

    public function test_chaining_all_ignore_options(): void
    {
        $diff = Diff::strings("  Hello  \n\n  WORLD  ", "hello\nworld")
            ->ignoreWhitespace()
            ->ignoreCase()
            ->ignoreBlankLines();

        $this->assertFalse($diff->hasChanges());
    }

    // -------------------------------------------------------------------------
    // Unified output with ignore options
    // -------------------------------------------------------------------------

    public function test_ignore_options_affect_unified_output(): void
    {
        $diff = Diff::strings('Hello', 'hello')->ignoreCase();

        $this->assertSame('', $diff->toUnified());
    }
}
