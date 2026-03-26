<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Upstream;

/**
 * Factory interface for creating upstream request builders.
 */
interface RequestBuilderFactoryInterface
{
    public function createBuilder(): ApiRequestBuilder;

    public function get(string $url): ApiRequestBuilder;

    public function post(string $url): ApiRequestBuilder;

    public function put(string $url): ApiRequestBuilder;

    public function patch(string $url): ApiRequestBuilder;

    public function delete(string $url): ApiRequestBuilder;
}
