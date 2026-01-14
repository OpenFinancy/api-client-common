<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common;

use OpenFinancy\ApiClient\Common\Filter\FilterComponentInterface;

/**
 * Contract for interacting with the API Platform endpoints exposed by this service.
 */
interface ApiPlatformClientInterface
{
    /**
     * Fetch a collection resource, optionally applying composite filters and additional HTTP context.
     *
     * @param non-empty-string $resource
     *
     * @return array<mixed>
     */
    public function getCollection(string $resource, ?FilterComponentInterface $filters = null, array $context = []): array;

    /**
     * Fetch a single item resource.
     *
     * @param non-empty-string $resource
     * @param non-empty-string $id
     *
     * @return array<mixed>
     */
    public function getItem(string $resource, string $id, array $context = []): array;

    /**
     * Create a new resource.
     *
     * @param non-empty-string $resource
     * @param array<mixed>     $payload
     *
     * @return array<mixed>
     */
    public function createItem(string $resource, array $payload, array $context = []): array;

    /**
     * Replace a resource using PUT semantics.
     *
     * @param non-empty-string $resource
     * @param non-empty-string $id
     * @param array<mixed>     $payload
     *
     * @return array<mixed>
     */
    public function updateItem(string $resource, string $id, array $payload, array $context = []): array;

    /**
     * Partially update a resource using PATCH semantics.
     *
     * @param non-empty-string $resource
     * @param non-empty-string $id
     * @param array<mixed>     $payload
     *
     * @return array<mixed>
     */
    public function patchItem(string $resource, string $id, array $payload, array $context = []): array;

    /**
     * Delete a resource.
     *
     * @param non-empty-string $resource
     * @param non-empty-string $id
     */
    public function deleteItem(string $resource, string $id, array $context = []): void;
}


