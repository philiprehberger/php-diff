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
     * Generate a unified diff string.
     */
    public function toUnified(int $context = 3): string
    {
        if (! $this->hasChanges()) {
            return '';
        }

        $operations = $this->operations;
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
        foreach ($this->operations as $op) {
            if ($op['type'] !== 'unchanged') {
                return true;
            }
        }

        return false;
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
