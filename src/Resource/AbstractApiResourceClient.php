<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Resource;

use OpenFinancy\ApiClient\Common\ApiPlatformClientInterface;
use OpenFinancy\ApiClient\Common\Filter\FilterComponentInterface;

/**
 * Base implementation shared by all resource-specific clients.
 */
abstract class AbstractApiResourceClient
{
    public function __construct(protected readonly ApiPlatformClientInterface $client)
    {
    }

    /**
     * Fetch a collection of resources for the underlying endpoint.
     *
     * @return array<mixed>
     */
    protected function getCollection(?FilterComponentInterface $filters = null, array $context = []): array
    {
        return $this->client->getCollection($this->getResourcePath(), $filters, $context);
    }

    /**
     * Fetch a single resource by IRI identifier.
     *
     * @return array<mixed>
     */
    protected function getItem(string $id, array $context = []): array
    {
        return $this->client->getItem($this->getResourcePath(), $id, $context);
    }

    /**
     * Create a new resource.
     *
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    protected function createItem(array $payload, array $context = []): array
    {
        return $this->client->createItem($this->getResourcePath(), $payload, $context);
    }

    /**
     * Fully replace a resource via PUT.
     *
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    protected function updateItem(string $id, array $payload, array $context = []): array
    {
        return $this->client->updateItem($this->getResourcePath(), $id, $payload, $context);
    }

    /**
     * Partially update a resource via PATCH.
     *
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    protected function patchItem(string $id, array $payload, array $context = []): array
    {
        return $this->client->patchItem($this->getResourcePath(), $id, $payload, $context);
    }

    protected function deleteItem(string $id, array $context = []): void
    {
        $this->client->deleteItem($this->getResourcePath(), $id, $context);
    }

    /**
     * Return the API Platform resource path, e.g. "metal-prices".
     */
    abstract protected function getResourcePath(): string;
}


