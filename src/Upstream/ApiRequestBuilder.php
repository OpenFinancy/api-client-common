<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Upstream;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Builder pattern for upstream provider HTTP requests.
 */
final class ApiRequestBuilder
{
    private string $method = 'GET';
    private string $url = '';
    private array $headers = [];
    private array $query = [];
    private ?array $json = null;
    private ?string $body = null;
    private int $timeout = 30;
    private array $options = [];

    public function __construct(
        private readonly ApiClientInterface $client,
    ) {
    }

    public function method(string $method): self
    {
        $this->method = mb_strtoupper($method);

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function query(string $key, mixed $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    public function queryParams(array $params): self
    {
        $this->query = array_merge($this->query, $params);

        return $this;
    }

    public function json(array $data): self
    {
        $this->json = $data;
        $this->header('Content-Type', 'application/json');

        return $this;
    }

    public function body(string $body, string $contentType = 'application/json'): self
    {
        $this->body = $body;
        $this->header('Content-Type', $contentType);

        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function option(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Build the request options array for Symfony HttpClient.
     *
     * @return array<string, mixed>
     */
    public function buildOptions(): array
    {
        $options = array_merge([
            'headers' => $this->headers,
            'timeout' => $this->timeout,
        ], $this->options);

        if ([] !== $this->query) {
            $options['query'] = $this->query;
        }

        if (null !== $this->json) {
            $options['json'] = $this->json;
        } elseif (null !== $this->body) {
            $options['body'] = $this->body;
        }

        return $options;
    }

    public function execute(): ResponseInterface
    {
        return $this->client->execute($this);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
