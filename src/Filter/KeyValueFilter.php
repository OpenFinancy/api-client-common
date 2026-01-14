<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Filter;

/**
 * Simple scalar or list filter implemented as a key/value pair.
 */
final class KeyValueFilter implements FilterComponentInterface
{
    /**
     * @param scalar|list<scalar>|null $value
     */
    public function __construct(
        private readonly string $key,
        private readonly mixed $value,
        private readonly bool $allowNull = false,
    ) {
    }

    public function apply(array $parameters = []): array
    {
        if (null === $this->value && false === $this->allowNull) {
            return $parameters;
        }

        if (is_array($this->value) && [] === $this->value) {
            return $parameters;
        }

        $parameters[$this->key] = $this->value;

        return $parameters;
    }
}


