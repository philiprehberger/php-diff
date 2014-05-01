<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff\Algorithm;

/**
 * Myers diff algorithm implementation for computing the shortest edit script.
 */
final class Myers
{
    /**
     * Compute the diff between two arrays of lines.
     *
     * Returns an array of [type, value] pairs where type is 'added', 'removed', or 'unchanged'.
     *
     * @param  array<int, string>  $old
     * @param  array<int, string>  $new
     * @return array<int, array{type: string, value: string}>
     */
    public static function diff(array $old, array $new): array
    {
        $old = array_values($old);
        $new = array_values($new);

        $n = count($old);
        $m = count($new);
        $max = $n + $m;

        if ($max === 0) {
            return [];
        }

        $v = [1 => 0];
        $trace = [];

        for ($d = 0; $d <= $max; $d++) {
            $trace[] = $v;

            for ($k = -$d; $k <= $d; $k += 2) {
                if ($k === -$d || ($k !== $d && ($v[$k - 1] ?? 0) < ($v[$k + 1] ?? 0))) {
                    $x = $v[$k + 1] ?? 0;
                } else {
                    $x = ($v[$k - 1] ?? 0) + 1;
                }

                $y = $x - $k;

                while ($x < $n && $y < $m && $old[$x] === $new[$y]) {
                    $x++;
                    $y++;
                }

                $v[$k] = $x;

                if ($x >= $n && $y >= $m) {
                    return self::backtrack($trace, $old, $new, $d);
                }
            }
        }

        return [];
    }

    /**
     * Backtrack through the trace to build the edit script.
     *
     * @param  array<int, array<int, int>>  $trace
     * @param  array<int, string>  $old
     * @param  array<int, string>  $new
     * @return array<int, array{type: string, value: string}>
     */
    private static function backtrack(array $trace, array $old, array $new, int $d): array
    {
        $x = count($old);
        $y = count($new);
        $result = [];

        for ($i = $d; $i > 0; $i--) {
            $v = $trace[$i - 1];
            $k = $x - $y;

            if ($k === -$i || ($k !== $i && ($v[$k - 1] ?? 0) < ($v[$k + 1] ?? 0))) {
                $prevK = $k + 1;
            } else {
                $prevK = $k - 1;
            }

            $prevX = $v[$prevK] ?? 0;
            $prevY = $prevX - $prevK;

            while ($x > $prevX && $y > $prevY) {
                $x--;
                $y--;
                $result[] = ['type' => 'unchanged', 'value' => $old[$x]];
            }

            if ($x === $prevX && $y > $prevY) {
                $y--;
                $result[] = ['type' => 'added', 'value' => $new[$y]];
            } elseif ($y === $prevY && $x > $prevX) {
                $x--;
                $result[] = ['type' => 'removed', 'value' => $old[$x]];
            }
        }

        while ($x > 0 && $y > 0) {
            $x--;
            $y--;
            $result[] = ['type' => 'unchanged', 'value' => $old[$x]];
        }

        return array_reverse($result);
    }
}
