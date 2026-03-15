<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

/**
 * Represents the diff result between two objects.
 */
final class ObjectDiff
{
    /** @var array<int, PropertyChange> */
    private readonly array $changeList;

    /**
     * Create a new ObjectDiff instance.
     */
    public function __construct(object $old, object $new)
    {
        $this->changeList = $this->compute($old, $new);
    }

    /**
     * Get all property changes.
     *
     * @return array<int, PropertyChange>
     */
    public function changes(): array
    {
        return $this->changeList;
    }

    /**
     * Check whether the diff contains any changes.
     */
    public function hasChanges(): bool
    {
        return $this->changeList !== [];
    }

    /**
     * Compute the differences between two objects.
     *
     * @return array<int, PropertyChange>
     */
    private function compute(object $old, object $new): array
    {
        $oldProps = get_object_vars($old);
        $newProps = get_object_vars($new);

        $allKeys = array_unique(array_merge(array_keys($oldProps), array_keys($newProps)));
        $changes = [];

        foreach ($allKeys as $property) {
            $oldValue = $oldProps[$property] ?? null;
            $newValue = $newProps[$property] ?? null;

            $existsInOld = array_key_exists($property, $oldProps);
            $existsInNew = array_key_exists($property, $newProps);

            if (! $existsInOld || ! $existsInNew || $oldValue !== $newValue) {
                $changes[] = new PropertyChange(
                    property: $property,
                    from: $existsInOld ? $oldValue : null,
                    to: $existsInNew ? $newValue : null,
                );
            }
        }

        return $changes;
    }
}
