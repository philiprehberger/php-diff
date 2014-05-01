<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

/**
 * Represents a single change in an array diff.
 */
final readonly class Change
{
    /**
     * Create a new Change instance.
     *
     * @param  'added'|'removed'|'changed'  $type
     */
    public function __construct(
        public string|int $key,
        public mixed $old,
        public mixed $new,
        public string $type,
    ) {}
}
