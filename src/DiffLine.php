<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

/**
 * Represents a single line in a diff result.
 */
final readonly class DiffLine
{
    /**
     * Create a new DiffLine instance.
     *
     * @param  'added'|'removed'|'unchanged'  $type
     */
    public function __construct(
        public string $type,
        public string $content,
        public ?int $lineNumber = null,
    ) {}
}
