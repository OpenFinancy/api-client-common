<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use OpenFinancy\ApiClient\Common\Filter\AbstractFilterBuilder;
use OpenFinancy\ApiClient\Common\Filter\FilterComposite;
use OpenFinancy\ApiClient\Common\Filter\KeyValueFilter;
use OpenFinancy\ApiClient\Common\Filter\OrderFilter;
use OpenFinancy\ApiClient\Common\Filter\PaginationFilter;
use PHPUnit\Framework\TestCase;

final class FilterComponentsTest extends TestCase
{
    public function testKeyValueFilterSkipsNullWhenNotAllowed(): void
    {
        $filter = new KeyValueFilter('symbol', null, false);

        $result = $filter->apply(['existing' => 'value']);

        self::assertSame(['existing' => 'value'], $result);
    }

    public function testKeyValueFilterKeepsNullWhenAllowed(): void
    {
        $filter = new KeyValueFilter('symbol', null, true);

        $result = $filter->apply();

        self::assertArrayHasKey('symbol', $result);
        self::assertNull($result['symbol']);
    }

    public function testKeyValueFilterSkipsEmptyArray(): void
    {
        $filter = new KeyValueFilter('ids', []);

        $result = $filter->apply();

        self::assertSame([], $result);
    }

    public function testPaginationFilterValidatesArgumentsAndAppliesParameters(): void
    {
        $filter = new PaginationFilter(page: 2, itemsPerPage: 50);

        $result = $filter->apply();

        self::assertSame(['page' => 2, 'itemsPerPage' => 50], $result);
    }

    public function testPaginationFilterRejectsInvalidValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PaginationFilter(page: 0, itemsPerPage: 10);
    }

    public function testOrderFilterAppliesOrderParameter(): void
    {
        $filter = new OrderFilter('price', 'DESC');

        $result = $filter->apply();

        self::assertSame(['order[price]' => 'desc'], $result);
    }

    public function testOrderFilterRejectsInvalidDirection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OrderFilter('price', 'sideways');
    }

    public function testFilterCompositeChainsComponentsAndReportsEmptiness(): void
    {
        $first = new KeyValueFilter('symbol', 'BTC');
        $second = new KeyValueFilter('currency', 'EUR');

        $composite = new FilterComposite($first);
        self::assertFalse($composite->isEmpty());

        $composite->add($second);

        $result = $composite->apply();

        self::assertSame(['symbol' => 'BTC', 'currency' => 'EUR'], $result);
    }

    public function testAbstractFilterBuilderProvidesPaginationAndDateHelpers(): void
    {
        $builder = new class extends AbstractFilterBuilder {
            public function sortable(string $field, string $direction): self
            {
                return $this->addOrder($field, $direction);
            }

            public function dateBefore(string $field, DateTimeImmutable $date): self
            {
                return $this->addDateFilter($field, 'before', $date);
            }

            public function getBuiltParameters(): array
            {
                return $this->apply();
            }
        };

        $date = new DateTimeImmutable('2024-01-15T10:30:00+00:00');

        $builder
            ->page(3)
            ->itemsPerPage(25)
            ->sortable('price', 'asc')
            ->dateBefore('createdAt', $date);

        $params = $builder->getBuiltParameters();

        self::assertSame(3, $params['page']);
        self::assertSame(25, $params['itemsPerPage']);
        self::assertSame('asc', $params['order[price]']);
        self::assertArrayHasKey('createdAt[before]', $params);
        self::assertMatchesRegularExpression('/^\\d{4}-\\d{2}-\\d{2}T/', $params['createdAt[before]']);
        self::assertFalse($builder->isEmpty());
    }

    public function testAbstractFilterBuilderRejectsUnsupportedDateComparison(): void
    {
        $builder = new class extends AbstractFilterBuilder {
            public function invalidDateComparison(DateTimeImmutable $date): void
            {
                $this->addDateFilter('createdAt', 'between', $date);
            }
        };

        $this->expectException(InvalidArgumentException::class);

        $builder->invalidDateComparison(new DateTimeImmutable('2024-01-01'));
    }
}
