<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Upstream;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Interface for standardized upstream provider HTTP clients.
 *
 * These clients are used by rate microservices to call external upstream APIs
 * (e.g. Metal, CoinMarketCap, etc.), and are intentionally separated from
 * consumer-side ApiPlatform clients.
 */
interface ApiClientInterface
{
    public function execute(ApiRequestBuilder $builder): ResponseInterface;

    public function getRequestBuilderFactory(): RequestBuilderFactoryInterface;
}
