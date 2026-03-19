<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Filter;

/**
 * Component contract for building API Platform query strings.
 *
 * Implementations may be combined using the Composite pattern to produce complex filters.
 */
interface FilterComponentInterface
{
    /**
     * Apply the filter to the provided set of query parameters.
     *
     * @param array<string, scalar|list<scalar>|null> $parameters
     *
     * @return array<string, scalar|list<scalar>|null>
     */
    public function apply(array $parameters = []): array;
}
