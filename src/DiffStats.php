<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

/**
 * Statistics about a string diff result.
 */
final readonly class DiffStats
{
    /**
     * Create a new DiffStats instance.
     */
    public function __construct(
        public int $added,
        public int $removed,
        public int $unchanged,
    ) {}
}
