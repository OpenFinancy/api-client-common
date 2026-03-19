<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Filter;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Base builder using the Composite pattern to assemble API Platform filters.
 */
abstract class AbstractFilterBuilder implements FilterComponentInterface
{
    protected FilterComposite $composite;

    public function __construct()
    {
        $this->composite = new FilterComposite();
    }

    public function apply(array $parameters = []): array
    {
        return $this->composite->apply($parameters);
    }

    public function isEmpty(): bool
    {
        return $this->composite->isEmpty();
    }

    public function page(int $page): static
    {
        $this->composite->add(new PaginationFilter($page));

        return $this;
    }

    public function itemsPerPage(int $itemsPerPage): static
    {
        $this->composite->add(new PaginationFilter(null, $itemsPerPage));

        return $this;
    }

    protected function addKeyValue(string $key, mixed $value, bool $allowNull = false): static
    {
        $this->composite->add(new KeyValueFilter($key, $value, $allowNull));

        return $this;
    }

    protected function addOrder(string $field, string $direction): static
    {
        $this->composite->add(new OrderFilter($field, $direction));

        return $this;
    }

    /**
     * Adds a date filter, supporting ApiPlatform date filter syntax.
     */
    protected function addDateFilter(string $field, string $comparison, DateTimeInterface|string $value): static
    {
        $allowedComparisons = ['before', 'strictly_before', 'after', 'strictly_after'];

        if (!\in_array($comparison, $allowedComparisons, true)) {
            throw new InvalidArgumentException(\sprintf('Invalid date comparison "%s".', $comparison));
        }

        if ($value instanceof DateTimeInterface) {
            $value = $this->formatDate($value);
        }

        $this->addKeyValue(\sprintf('%s[%s]', $field, $comparison), $value);

        return $this;
    }

    /**
     * Format date values based on field specificity.
     */
    protected function formatDate(DateTimeInterface $dateTime, bool $withTime = true): string
    {
        return $withTime ? $dateTime->format(DateTimeInterface::ATOM) : $dateTime->format('Y-m-d');
    }
}
