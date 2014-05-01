<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

/**
 * Represents the diff result between two arrays.
 */
final class ArrayDiff
{
    /** @var array<int, Change> */
    private readonly array $changeList;

    /**
     * Create a new ArrayDiff instance.
     *
     * @param  array<mixed>  $old
     * @param  array<mixed>  $new
     */
    public function __construct(array $old, array $new)
    {
        $this->changeList = $this->compute($old, $new);
    }

    /**
     * Get all changes.
     *
     * @return array<int, Change>
     */
    public function changes(): array
    {
        return $this->changeList;
    }

    /**
     * Get only added entries.
     *
     * @return array<int, Change>
     */
    public function added(): array
    {
        return array_values(array_filter(
            $this->changeList,
            static fn (Change $change): bool => $change->type === 'added',
        ));
    }

    /**
     * Get only removed entries.
     *
     * @return array<int, Change>
     */
    public function removed(): array
    {
        return array_values(array_filter(
            $this->changeList,
            static fn (Change $change): bool => $change->type === 'removed',
        ));
    }

    /**
     * Get only changed entries.
     *
     * @return array<int, Change>
     */
    public function changed(): array
    {
        return array_values(array_filter(
            $this->changeList,
            static fn (Change $change): bool => $change->type === 'changed',
        ));
    }

    /**
     * Check whether the diff contains any changes.
     */
    public function hasChanges(): bool
    {
        return $this->changeList !== [];
    }

    /**
     * Compute the differences between two arrays.
     *
     * @param  array<mixed>  $old
     * @param  array<mixed>  $new
     * @return array<int, Change>
     */
    private function compute(array $old, array $new): array
    {
        $changes = [];

        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            $existsInOld = array_key_exists($key, $old);
            $existsInNew = array_key_exists($key, $new);

            if ($existsInOld && ! $existsInNew) {
                $changes[] = new Change(
                    key: $key,
                    old: $old[$key],
                    new: null,
                    type: 'removed',
                );
            } elseif (! $existsInOld && $existsInNew) {
                $changes[] = new Change(
                    key: $key,
                    old: null,
                    new: $new[$key],
                    type: 'added',
                );
            } elseif ($old[$key] !== $new[$key]) {
                $changes[] = new Change(
                    key: $key,
                    old: $old[$key],
                    new: $new[$key],
                    type: 'changed',
                );
            }
        }

        return $changes;
    }
}
