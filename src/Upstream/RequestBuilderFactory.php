<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Upstream;

/**
 * Factory for creating upstream request builders.
 */
final class RequestBuilderFactory implements RequestBuilderFactoryInterface
{
    public function __construct(
        private readonly ApiClientInterface $client,
    ) {
    }

    public function createBuilder(): ApiRequestBuilder
    {
        return new ApiRequestBuilder($this->client);
    }

    public function get(string $url): ApiRequestBuilder
    {
        return $this->createBuilder()
            ->method('GET')
            ->url($url);
    }

    public function post(string $url): ApiRequestBuilder
    {
        return $this->createBuilder()
            ->method('POST')
            ->url($url);
    }

    public function put(string $url): ApiRequestBuilder
    {
        return $this->createBuilder()
            ->method('PUT')
            ->url($url);
    }

    public function patch(string $url): ApiRequestBuilder
    {
        return $this->createBuilder()
            ->method('PATCH')
            ->url($url);
    }

    public function delete(string $url): ApiRequestBuilder
    {
        return $this->createBuilder()
            ->method('DELETE')
            ->url($url);
    }
}
