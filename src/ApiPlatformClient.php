<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common;

use OpenFinancy\ApiClient\Common\Exception\ApiPlatformClientException;
use OpenFinancy\ApiClient\Common\Filter\FilterComponentInterface;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * HTTP client dedicated to interacting with this service's API Platform endpoints.
 */
class ApiPlatformClient implements ApiPlatformClientInterface
{
    private const DEFAULT_HEADERS = [
        'Accept' => 'application/ld+json',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUri,
        private readonly ?CacheInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        if ('' === trim($baseUri)) {
            throw new ApiPlatformClientException('API Platform base URI cannot be empty.');
        }
    }

    public function getCollection(string $resource, ?FilterComponentInterface $filters = null, array $context = []): array
    {
        $options = $this->buildOptions($filters, $context);

        return $this->request('GET', $this->buildUri($resource), $options);
    }

    public function getItem(string $resource, string $id, array $context = []): array
    {
        $options = $this->buildOptions(null, $context);

        return $this->request('GET', $this->buildUri($resource, $id), $options);
    }

    public function createItem(string $resource, array $payload, array $context = []): array
    {
        $options = $this->buildOptions(null, $context);
        $options['json'] = $payload;

        return $this->request('POST', $this->buildUri($resource), $options);
    }

    public function updateItem(string $resource, string $id, array $payload, array $context = []): array
    {
        $options = $this->buildOptions(null, $context);
        $options['json'] = $payload;

        return $this->request('PUT', $this->buildUri($resource, $id), $options);
    }

    public function patchItem(string $resource, string $id, array $payload, array $context = []): array
    {
        $options = $this->buildOptions(null, $context);
        $options['json'] = $payload;

        return $this->request('PATCH', $this->buildUri($resource, $id), $options);
    }

    public function deleteItem(string $resource, string $id, array $context = []): void
    {
        $options = $this->buildOptions(null, $context);

        $this->request('DELETE', $this->buildUri($resource, $id), $options);
    }

    /**
     * @return array<mixed>
     */
    private function request(string $method, string $uri, array $options): array
    {
        $options['headers'] = array_merge(self::DEFAULT_HEADERS, $options['headers'] ?? []);

        // Set Content-Type header for write operations with JSON payload
        if (isset($options['json']) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if ($method === 'PATCH') {
                $options['headers']['Content-Type'] = 'application/merge-patch+json';
            } else {
                $options['headers']['Content-Type'] = 'application/ld+json';
            }
        }

        // Log the actual HTTP request being made
        $fullUrl = $uri;
        if (isset($options['query']) && is_array($options['query'])) {
            $fullUrl .= '?' . http_build_query($options['query']);
        }
        $this->log('debug', sprintf('Making %s request to: %s', $method, $fullUrl));

        try {
            $response = $this->httpClient->request($method, $uri, $options);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            $this->log('debug', sprintf('Response status: %d, content length: %d', $statusCode, strlen($content)));
        } catch (TransportExceptionInterface|HttpExceptionInterface $exception) {
            $this->log('error', sprintf('Request failed: %s', $exception->getMessage()));
            throw new ApiPlatformClientException(
                sprintf('Transport error while calling "%s %s": %s', $method, $uri, $exception->getMessage()),
                null,
                $exception,
            );
        }

        if ($statusCode >= 400) {
            throw new ApiPlatformClientException(
                sprintf('API Platform request to "%s %s" failed with status %d: %s', $method, $uri, $statusCode, $content),
                $statusCode,
            );
        }

        if ('' === trim($content)) {
            return [];
        }

        try {
            /** @var array<mixed> $decoded */
            $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ApiPlatformClientException(
                sprintf('Unable to decode API Platform response from "%s %s": %s', $method, $uri, $exception->getMessage()),
                $statusCode,
                $exception,
            );
        }

        return $decoded;
    }

    /**
     * Log a message if logger is available.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $message, $context);
        }
    }

    private function buildUri(string $resource, ?string $id = null): string
    {
        $path = trim($resource, '/');

        if ('' === $path) {
            throw new ApiPlatformClientException('Resource path cannot be empty.');
        }

        if (null !== $id && '' === trim($id)) {
            throw new ApiPlatformClientException('Resource identifier cannot be empty.');
        }

        if (null !== $id) {
            $path .= '/' . rawurlencode(trim($id, '/'));
        }

        return sprintf('%s/%s', rtrim($this->baseUri, '/'), $path);
    }

    /**
     * @return array<mixed>
     */
    private function buildOptions(?FilterComponentInterface $filters, array $context): array
    {
        $options = $context;
        $query = $context['query'] ?? [];

        if ($filters instanceof FilterComponentInterface) {
            $query = $filters->apply(is_array($query) ? $query : []);
        }

        if ([] !== $query) {
            $options['query'] = $query;
        } else {
            unset($options['query']);
        }

        return $options;
    }
}


