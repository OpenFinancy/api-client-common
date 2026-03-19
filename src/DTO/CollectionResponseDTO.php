<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\DTO;

/**
 * Base class for API Platform collection responses.
 * Handles both full JSON-LD format (hydra:member) and short JSON-LD format (member).
 *
 * API Platform can return responses in two formats:
 * - Full JSON-LD: Uses full IRIs like "hydra:member", "hydra:totalItems"
 * - Short JSON-LD: Uses short names like "member", "totalItems" (defined in @context)
 *
 * This class handles both formats automatically.
 */
abstract class CollectionResponseDTO
{
    /**
     * @param array<mixed>              $items           Array of item DTOs
     * @param int                       $totalItems      Total number of items
     * @param array<string, mixed>|null $view            Pagination view metadata
     * @param string|null               $nextPageUrl     Next page URL if available
     * @param string|null               $previousPageUrl Previous page URL if available
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
     * Handles both full JSON-LD format (hydra:member) and short JSON-LD format (member).
     *
     * @param array<string, mixed> $response API Platform response (full or short JSON-LD format)
     */
    public static function fromApiResponse(array $response): static
    {
        // Handle both full JSON-LD format (hydra:member) and short JSON-LD format (member)
        // API Platform may return either format depending on configuration
        $members = $response['hydra:member'] ?? $response['member'] ?? [];

        // Handle totalItems in both formats
        $totalItems = $response['hydra:totalItems'] ?? $response['totalItems'] ?? (\is_array($members) ? \count($members) : 0);

        // Handle view/pagination in both formats
        $view = $response['hydra:view'] ?? $response['view'] ?? null;

        $items = [];
        if (\is_array($members)) {
            foreach ($members as $member) {
                if (\is_array($member)) {
                    $items[] = static::createItemFromArray($member);
                }
            }
        }

        $nextPageUrl = null;
        $previousPageUrl = null;
        if (null !== $view && \is_array($view)) {
            // Handle pagination links in both formats
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
        return null !== $this->nextPageUrl;
    }

    /**
     * Check if there is a previous page.
     */
    public function hasPreviousPage(): bool
    {
        return null !== $this->previousPageUrl;
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
     * Outputs in full JSON-LD format by default (hydra:member, hydra:totalItems, hydra:view).
     * Properly reconstructs pagination view with next/previous URLs if available.
     *
     * @param bool $shortFormat If true, outputs in short JSON-LD format (member, totalItems, view)
     *
     * @return array<string, mixed>
     */
    public function toArray(bool $shortFormat = false): array
    {
        $memberKey = $shortFormat ? 'member' : 'hydra:member';
        $totalItemsKey = $shortFormat ? 'totalItems' : 'hydra:totalItems';
        $viewKey = $shortFormat ? 'view' : 'hydra:view';
        $nextKey = $shortFormat ? 'next' : 'hydra:next';
        $previousKey = $shortFormat ? 'previous' : 'hydra:previous';

        $result = [
            $memberKey => array_map(
                static fn (mixed $item): mixed => \is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item,
                $this->items
            ),
            $totalItemsKey => $this->totalItems,
        ];

        // Reconstruct view with pagination links if they exist
        $view = $this->view ?? [];
        if (null !== $this->nextPageUrl || null !== $this->previousPageUrl) {
            if (!\is_array($view)) {
                $view = [];
            }
            if (null !== $this->nextPageUrl) {
                $view[$nextKey] = $this->nextPageUrl;
            }
            if (null !== $this->previousPageUrl) {
                $view[$previousKey] = $this->previousPageUrl;
            }
        }

        if ([] !== $view) {
            $result[$viewKey] = $view;
        }

        return $result;
    }
}
