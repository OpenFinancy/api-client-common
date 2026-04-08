<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Tests\Unit;

use OpenFinancy\ApiClient\Common\DTO\CollectionResponseDTO;
use PHPUnit\Framework\TestCase;

final class CollectionResponseDTOTest extends TestCase
{
    public function testFromApiResponseHandlesFullJsonLdFormat(): void
    {
        $response = [
            'hydra:member' => [
                ['id' => 1, 'name' => 'First'],
                ['id' => 2, 'name' => 'Second'],
            ],
            'hydra:totalItems' => 2,
            'hydra:view' => [
                '@id' => '/resources?page=1',
                'hydra:next' => '/resources?page=2',
            ],
        ];

        $dto = TestCollectionResponseDTO::fromApiResponse($response);

        self::assertCount(2, $dto->getItems());
        self::assertSame(2, $dto->getTotalItems());
        self::assertTrue($dto->hasNextPage());
        self::assertFalse($dto->hasPreviousPage());
        self::assertSame('/resources?page=2', $dto->getNextPageUrl());
        self::assertNull($dto->getPreviousPageUrl());
    }

    public function testFromApiResponseHandlesShortJsonLdFormat(): void
    {
        $response = [
            'member' => [
                ['id' => 1, 'name' => 'First'],
            ],
            'totalItems' => 5,
            'view' => [
                '@id' => '/resources?page=3',
                'next' => '/resources?page=4',
                'previous' => '/resources?page=2',
            ],
        ];

        $dto = TestCollectionResponseDTO::fromApiResponse($response);

        self::assertCount(1, $dto->getItems());
        self::assertSame(5, $dto->getTotalItems());
        self::assertTrue($dto->hasNextPage());
        self::assertTrue($dto->hasPreviousPage());
        self::assertSame('/resources?page=4', $dto->getNextPageUrl());
        self::assertSame('/resources?page=2', $dto->getPreviousPageUrl());
    }

    public function testToArrayOutputsFullJsonLdByDefault(): void
    {
        $dto = new TestCollectionResponseDTO(
            items: [new TestItemDTO(1), new TestItemDTO(2)],
            totalItems: 2,
            view: ['@id' => '/resources?page=1'],
            nextPageUrl: '/resources?page=2',
            previousPageUrl: '/resources?page=0',
        );

        $data = $dto->toArray();

        self::assertArrayHasKey('hydra:member', $data);
        self::assertArrayHasKey('hydra:totalItems', $data);
        self::assertArrayHasKey('hydra:view', $data);
        self::assertSame(2, $data['hydra:totalItems']);
        self::assertSame('/resources?page=2', $data['hydra:view']['hydra:next']);
        self::assertSame('/resources?page=0', $data['hydra:view']['hydra:previous']);
    }

    public function testToArraySupportsShortFormat(): void
    {
        $dto = new TestCollectionResponseDTO(
            items: [new TestItemDTO(1)],
            totalItems: 1,
            view: ['@id' => '/resources?page=1'],
            nextPageUrl: '/resources?page=2',
        );

        $data = $dto->toArray(shortFormat: true);

        self::assertArrayHasKey('member', $data);
        self::assertArrayHasKey('totalItems', $data);
        self::assertArrayHasKey('view', $data);
        self::assertSame(1, $data['totalItems']);
        self::assertSame('/resources?page=2', $data['view']['next']);
        self::assertArrayNotHasKey('previous', $data['view']);
    }
}

final class TestItemDTO
{
    public function __construct(
        public readonly int $id,
    ) {
    }

    public function toArray(): array
    {
        return ['id' => $this->id];
    }
}

final class TestCollectionResponseDTO extends CollectionResponseDTO
{
    protected static function createItemFromArray(array $data): mixed
    {
        return new TestItemDTO($data['id']);
    }
}
