<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

/**
 * Static API for computing diffs between strings, arrays, and objects.
 */
final class Diff
{
    /**
     * Compute the diff between two strings.
     */
    public static function strings(string $old, string $new): StringDiff
    {
        $oldLines = $old !== '' ? explode("\n", $old) : [];
        $newLines = $new !== '' ? explode("\n", $new) : [];

        return new StringDiff($oldLines, $newLines);
    }

    /**
     * Compute the diff between two arrays.
     *
     * @param  array<mixed>  $old
     * @param  array<mixed>  $new
     */
    public static function arrays(array $old, array $new): ArrayDiff
    {
        return new ArrayDiff($old, $new);
    }

    /**
     * Compute the diff between two objects.
     */
    public static function objects(object $old, object $new): ObjectDiff
    {
        return new ObjectDiff($old, $new);
    }
}
