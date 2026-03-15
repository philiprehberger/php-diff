<?php

declare(strict_types=1);

namespace PhilipRehberger\Diff;

/**
 * Represents a property change between two objects.
 */
final readonly class PropertyChange
{
    /**
     * Create a new PropertyChange instance.
     */
    public function __construct(
        public string $property,
        public mixed $from,
        public mixed $to,
    ) {}
}
