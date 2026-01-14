<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Filter;

use InvalidArgumentException;

/**
 * Filter component supporting ApiPlatform pagination parameters.
 */
final class PaginationFilter implements FilterComponentInterface
{
    public function __construct(
        private readonly ?int $page = null,
        private readonly ?int $itemsPerPage = null,
    ) {
        if (null !== $this->page && $this->page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }

        if (null !== $this->itemsPerPage && $this->itemsPerPage < 1) {
            throw new InvalidArgumentException('Items per page must be greater than or equal to 1.');
        }
    }

    public function apply(array $parameters = []): array
    {
        if (null !== $this->page) {
            $parameters['page'] = $this->page;
        }

        if (null !== $this->itemsPerPage) {
            $parameters['itemsPerPage'] = $this->itemsPerPage;
        }

        return $parameters;
    }
}


