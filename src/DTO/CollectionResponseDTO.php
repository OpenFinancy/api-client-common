<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\DTO;

/**
 * Base class for API Platform collection responses.
 * Handles JSON-LD format with hydra:member and pagination metadata.
 */
abstract class CollectionResponseDTO
{
    /**
     * @param array<mixed> $items Array of item DTOs
     * @param int $totalItems Total number of items
     * @param array<string, mixed>|null $view Pagination view metadata
     * @param string|null $nextPageUrl Next page URL if available
     * @param string|null $previousPageUrl Previous page URL if available
     */
    public function __construct(
        public readonly array $items,
        public readonly int $totalItems = 0,
        public readonly ?array $view = null,
        public readonly ?string $nextPageUrl = null,
        public readonly ?string $previousPageUrl = null,
    ) {
    }

    /**
     * Create DTO from API Platform JSON-LD response.
     *
     * @param array<string, mixed> $response API Platform response
     * @return static
     */
    public static function fromApiResponse(array $response): static
    {
        // Handle API Platform JSON-LD format
        $members = $response['hydra:member'] ?? $response['member'] ?? [];
        $totalItems = $response['hydra:totalItems'] ?? $response['totalItems'] ?? count($members);
        $view = $response['hydra:view'] ?? $response['view'] ?? null;

        $items = [];
        foreach ($members as $member) {
            $items[] = static::createItemFromArray($member);
        }

        $nextPageUrl = null;
        $previousPageUrl = null;
        if ($view !== null) {
            $nextPageUrl = $view['hydra:next'] ?? $view['next'] ?? null;
            $previousPageUrl = $view['hydra:previous'] ?? $view['previous'] ?? null;
        }

        return new static($items, $totalItems, $view, $nextPageUrl, $previousPageUrl);
    }

    /**
     * Create an item DTO from array data.
     * Must be implemented by subclasses.
     *
     * @param array<string, mixed> $data
     * @return mixed
     */
    abstract protected static function createItemFromArray(array $data): mixed;

    /**
     * Get items as array.
     *
     * @return array<mixed>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get total number of items.
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Check if there is a next page.
     */
    public function hasNextPage(): bool
    {
        return $this->nextPageUrl !== null;
    }

    /**
     * Check if there is a previous page.
     */
    public function hasPreviousPage(): bool
    {
        return $this->previousPageUrl !== null;
    }

    /**
     * Get next page URL.
     */
    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }

    /**
     * Get previous page URL.
     */
    public function getPreviousPageUrl(): ?string
    {
        return $this->previousPageUrl;
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'hydra:member' => array_map(
                fn($item) => method_exists($item, 'toArray') ? $item->toArray() : $item,
                $this->items
            ),
            'hydra:totalItems' => $this->totalItems,
        ];

        if ($this->view !== null) {
            $result['hydra:view'] = $this->view;
        }

        return $result;
    }
}

