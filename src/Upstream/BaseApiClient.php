<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Upstream;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

/**
 * Base implementation for upstream provider HTTP clients.
 */
abstract class BaseApiClient implements ApiClientInterface
{
    private ?RequestBuilderFactoryInterface $factory = null;

    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly string $userAgent = 'OpenFinancy/rate-microservice-core/1.0',
    ) {
    }

    abstract protected function getBaseUrl(): string;

    /**
     * @return array<string, string>
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => $this->userAgent,
        ];
    }

    protected function getDefaultTimeout(): int
    {
        return 30;
    }

    public function execute(ApiRequestBuilder $builder): ResponseInterface
    {
        $url = $this->getBaseUrl() . $builder->getUrl();
        $method = $builder->getMethod();
        $options = $builder->buildOptions();
        $requestHeaders = \is_array($options['headers'] ?? null) ? $options['headers'] : [];

        $options['headers'] = array_merge(
            $this->getDefaultHeaders(),
            $requestHeaders,
        );

        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->getDefaultTimeout();
        }

        $this->logRequest($method, $url, $options);

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $this->logResponse($response);

            return $response;
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            $this->logError($method, $url, $e);
            throw $e;
        }
    }

    public function getRequestBuilderFactory(): RequestBuilderFactoryInterface
    {
        if (null === $this->factory) {
            $this->factory = new RequestBuilderFactory($this);
        }

        return $this->factory;
    }

    protected function logRequest(string $method, string $url, array $options): void
    {
        if (null === $this->logger) {
            return;
        }

        $headers = $options['headers'] ?? [];
        $safeHeaders = [];
        foreach ($headers as $key => $value) {
            if (false === mb_stripos((string) $key, 'authorization') && false === mb_stripos((string) $key, 'api-key')) {
                $safeHeaders[$key] = $value;
            } else {
                $safeHeaders[$key] = '***REDACTED***';
            }
        }

        $this->logger->debug('API Request', [
            'method' => $method,
            'url' => $url,
            'query' => $options['query'] ?? [],
            'headers' => $safeHeaders,
        ]);
    }

    protected function logResponse(ResponseInterface $response): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->debug('API Response', [
            'status_code' => $response->getStatusCode(),
            'headers' => $response->getHeaders(false),
        ]);
    }

    protected function logError(string $method, string $url, Throwable $e): void
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->error('API Request Failed', [
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ]);
    }
}
