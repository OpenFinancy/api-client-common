<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Filter;

use InvalidArgumentException;

/**
 * Filter component responsible for ApiPlatform style order directives.
 */
final class OrderFilter implements FilterComponentInterface
{
    private readonly string $direction;

    public function __construct(private readonly string $field, string $direction)
    {
        $direction = strtolower($direction);

        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException(sprintf('Invalid sort direction "%s". Allowed values: asc, desc.', $direction));
        }

        $this->direction = $direction;
    }

    public function apply(array $parameters = []): array
    {
        $parameters[sprintf('order[%s]', $this->field)] = $this->direction;

        return $parameters;
    }
}


