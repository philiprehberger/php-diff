<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

use PhilipRehberger\Diff\Algorithm\Myers;

/**
 * Represents the diff result between two strings.
 */
final class StringDiff
{
    /** @var array<int, array{type: string, value: string}> */
    private readonly array $operations;

    private bool $ignoreWhitespaceOption = false;

    private bool $ignoreCaseOption = false;

    private bool $ignoreBlankLinesOption = false;

    /**
     * Create a new StringDiff instance.
     *
     * @param  array<int, string>  $oldLines
     * @param  array<int, string>  $newLines
     */
    public function __construct(
        private readonly array $oldLines,
        private readonly array $newLines,
    ) {
        $this->operations = Myers::diff($this->oldLines, $this->newLines);
    }

    /**
     * Create a new StringDiff with whitespace normalization applied.
     */
    public function ignoreWhitespace(): self
    {
        $clone = clone $this;
        $clone->ignoreWhitespaceOption = true;

        return $clone;
    }

    /**
     * Create a new StringDiff with case-insensitive comparison.
     */
    public function ignoreCase(): self
    {
        $clone = clone $this;
        $clone->ignoreCaseOption = true;

        return $clone;
    }

    /**
     * Create a new StringDiff with blank line removal applied.
     */
    public function ignoreBlankLines(): self
    {
        $clone = clone $this;
        $clone->ignoreBlankLinesOption = true;

        return $clone;
    }

    /**
     * Generate a unified diff string.
     */
    public function toUnified(int $context = 3): string
    {
        $operations = $this->resolveOperations();

        if (! $this->operationsHaveChanges($operations)) {
            return '';
        }

        $lines = [];
        $hunks = $this->buildHunks($operations, $context);

        foreach ($hunks as $hunk) {
            $lines[] = sprintf(
                '@@ -%d,%d +%d,%d @@',
                $hunk['oldStart'],
                $hunk['oldCount'],
                $hunk['newStart'],
                $hunk['newCount'],
            );

            foreach ($hunk['lines'] as $line) {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Generate an ANSI-colored unified diff string for terminal output.
     */
    public function toAnsi(int $context = 3): string
    {
        $unified = $this->toUnified($context);

        if ($unified === '') {
            return '';
        }

        $lines = explode("\n", $unified);
        $colored = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '@@')) {
                $colored[] = "\033[36m".$line."\033[0m";
            } elseif (str_starts_with($line, '-')) {
                $colored[] = "\033[31m".$line."\033[0m";
            } elseif (str_starts_with($line, '+')) {
                $colored[] = "\033[32m".$line."\033[0m";
            } else {
                $colored[] = $line;
            }
        }

        return implode("\n", $colored);
    }

    /**
     * Generate an HTML representation of the diff.
     */
    public function toHtml(): string
    {
        $html = '<div class="diff">';

        foreach ($this->operations as $op) {
            $escaped = htmlspecialchars($op['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $html .= match ($op['type']) {
                'added' => '<ins class="diff-added">'.$escaped.'</ins>',
                'removed' => '<del class="diff-removed">'.$escaped.'</del>',
                default => '<span class="diff-unchanged">'.$escaped.'</span>',
            };
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get the diff as an array of DiffLine objects.
     *
     * @return array<int, DiffLine>
     */
    public function toArray(): array
    {
        $result = [];
        $lineNumber = 0;

        foreach ($this->operations as $op) {
            $lineNumber++;
            $result[] = new DiffLine(
                type: $op['type'],
                content: $op['value'],
                lineNumber: $lineNumber,
            );
        }

        return $result;
    }

    /**
     * Check whether the diff contains any changes.
     */
    public function hasChanges(): bool
    {
        return $this->operationsHaveChanges($this->resolveOperations());
    }

    /**
     * Calculate the similarity ratio between the two texts.
     *
     * Returns a float between 0.0 (completely different) and 1.0 (identical).
     */
    public function similarity(): float
    {
        $total = count($this->operations);

        if ($total === 0) {
            return 1.0;
        }

        $unchanged = 0;

        foreach ($this->operations as $op) {
            if ($op['type'] === 'unchanged') {
                $unchanged++;
            }
        }

        return $unchanged / $total;
    }

    /**
     * Generate a side-by-side HTML diff with two columns.
     */
    public function toHtmlSideBySide(): string
    {
        $html = '<table class="diff-table">';

        foreach ($this->operations as $op) {
            $escaped = htmlspecialchars($op['value'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $html .= match ($op['type']) {
                'removed' => '<tr>'
                    .'<td class="diff-left diff-removed">'.$escaped.'</td>'
                    .'<td class="diff-right"></td>'
                    .'</tr>',
                'added' => '<tr>'
                    .'<td class="diff-left"></td>'
                    .'<td class="diff-right diff-added">'.$escaped.'</td>'
                    .'</tr>',
                default => '<tr>'
                    .'<td class="diff-left diff-unchanged">'.$escaped.'</td>'
                    .'<td class="diff-right diff-unchanged">'.$escaped.'</td>'
                    .'</tr>',
            };
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Get statistics about the diff.
     */
    public function stats(): DiffStats
    {
        $added = 0;
        $removed = 0;
        $unchanged = 0;

        foreach ($this->operations as $op) {
            match ($op['type']) {
                'added' => $added++,
                'removed' => $removed++,
                default => $unchanged++,
            };
        }

        return new DiffStats(
            added: $added,
            removed: $removed,
            unchanged: $unchanged,
        );
    }

    /**
     * Resolve operations considering ignore options.
     *
     * When ignore options are set, the diff is recomputed with normalized lines.
     *
     * @return array<int, array{type: string, value: string}>
     */
    private function resolveOperations(): array
    {
        if (! $this->ignoreWhitespaceOption && ! $this->ignoreCaseOption && ! $this->ignoreBlankLinesOption) {
            return $this->operations;
        }

        $oldLines = $this->oldLines;
        $newLines = $this->newLines;

        if ($this->ignoreBlankLinesOption) {
            $oldLines = array_values(array_filter($oldLines, static fn (string $line): bool => trim($line) !== ''));
            $newLines = array_values(array_filter($newLines, static fn (string $line): bool => trim($line) !== ''));
        }

        $normalizedOld = $oldLines;
        $normalizedNew = $newLines;

        if ($this->ignoreWhitespaceOption) {
            $normalizedOld = array_map(static fn (string $line): string => preg_replace('/\s+/', ' ', trim($line)) ?? $line, $normalizedOld);
            $normalizedNew = array_map(static fn (string $line): string => preg_replace('/\s+/', ' ', trim($line)) ?? $line, $normalizedNew);
        }

        if ($this->ignoreCaseOption) {
            $normalizedOld = array_map(static fn (string $line): string => mb_strtolower($line), $normalizedOld);
            $normalizedNew = array_map(static fn (string $line): string => mb_strtolower($line), $normalizedNew);
        }

        return Myers::diff($normalizedOld, $normalizedNew);
    }

    /**
     * Check whether operations contain any changes.
     *
     * @param  array<int, array{type: string, value: string}>  $operations
     */
    private function operationsHaveChanges(array $operations): bool
    {
        foreach ($operations as $op) {
            if ($op['type'] !== 'unchanged') {
                return true;
            }
        }

        return false;
    }

    /**
     * Build hunks for unified diff output.
     *
     * @param  array<int, array{type: string, value: string}>  $operations
     * @return array<int, array{oldStart: int, oldCount: int, newStart: int, newCount: int, lines: array<int, string>}>
     */
    private function buildHunks(array $operations, int $context): array
    {
        $hunks = [];
        $currentHunk = null;
        $oldLine = 0;
        $newLine = 0;
        $lastChangeIndex = -1;

        // Find indices of changes
        $changeIndices = [];
        foreach ($operations as $i => $op) {
            if ($op['type'] !== 'unchanged') {
                $changeIndices[] = $i;
            }
        }

        if ($changeIndices === []) {
            return [];
        }

        $oldLine = 1;
        $newLine = 1;

        $inHunk = false;
        $hunkLines = [];
        $hunkOldStart = 1;
        $hunkNewStart = 1;
        $hunkOldCount = 0;
        $hunkNewCount = 0;

        foreach ($operations as $i => $op) {
            $nearChange = false;
            foreach ($changeIndices as $ci) {
                if (abs($i - $ci) <= $context) {
                    $nearChange = true;
                    break;
                }
            }

            if ($nearChange || $op['type'] !== 'unchanged') {
                if (! $inHunk) {
                    $inHunk = true;
                    $hunkLines = [];
                    $hunkOldStart = $oldLine;
                    $hunkNewStart = $newLine;
                    $hunkOldCount = 0;
                    $hunkNewCount = 0;
                }

                match ($op['type']) {
                    'added' => $hunkLines[] = '+'.$op['value'],
                    'removed' => $hunkLines[] = '-'.$op['value'],
                    default => $hunkLines[] = ' '.$op['value'],
                };

                if ($op['type'] === 'removed' || $op['type'] === 'unchanged') {
                    $hunkOldCount++;
                }
                if ($op['type'] === 'added' || $op['type'] === 'unchanged') {
                    $hunkNewCount++;
                }
            } elseif ($inHunk) {
                $hunks[] = [
                    'oldStart' => $hunkOldStart,
                    'oldCount' => $hunkOldCount,
                    'newStart' => $hunkNewStart,
                    'newCount' => $hunkNewCount,
                    'lines' => $hunkLines,
                ];
                $inHunk = false;
            }

            if ($op['type'] === 'removed' || $op['type'] === 'unchanged') {
                $oldLine++;
            }
            if ($op['type'] === 'added' || $op['type'] === 'unchanged') {
                $newLine++;
            }
        }

        if ($inHunk) {
            $hunks[] = [
                'oldStart' => $hunkOldStart,
                'oldCount' => $hunkOldCount,
                'newStart' => $hunkNewStart,
                'newCount' => $hunkNewCount,
                'lines' => $hunkLines,
            ];
        }

        return $hunks;
    }
}
