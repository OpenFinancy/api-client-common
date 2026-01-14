# OpenFinancy API Client Common

Common API client components for OpenFinancy API clients. This package provides the base functionality used by all OpenFinancy API client packages.

## Installation

```bash
composer require openfinancy/api-client-common
```

## Requirements

- PHP >= 8.4
- Symfony HTTP Client ^7.3
- Symfony Cache Contracts ^3.0

## Overview

This package provides the foundational components for building API Platform clients:

- **ApiPlatformClient**: HTTP client for interacting with API Platform endpoints
- **AbstractApiResourceClient**: Base class for resource-specific clients
- **Filter System**: Composite pattern-based filter builders
- **DTOs**: Data Transfer Objects for API responses
- **Exception Handling**: Domain-specific exceptions

## Architecture

The package uses several design patterns:

- **Composite Pattern**: For building complex filter queries
- **Template Method Pattern**: AbstractApiResourceClient provides template methods
- **Strategy Pattern**: Filter components can be combined flexibly

### Class Hierarchy

```
ApiPlatformClientInterface
    └── ApiPlatformClient

FilterComponentInterface
    ├── FilterComposite
    ├── KeyValueFilter
    ├── OrderFilter
    ├── PaginationFilter
    └── AbstractFilterBuilder

AbstractApiResourceClient
    └── (Extended by specific resource clients)
```

## Quick Start

### Basic Usage

```php
use OpenFinancy\ApiClient\Common\ApiPlatformClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

$httpClient = // ... your HTTP client instance
$baseUri = 'https://api.example.com';

$client = new ApiPlatformClient($httpClient, $baseUri);

// Fetch a collection
$response = $client->getCollection('exchange-rates');

// Fetch a single item
$item = $client->getItem('exchange-rates', '123');

// Create a new resource
$newItem = $client->createItem('exchange-rates', [
    'baseCurrency' => 'USD',
    'quoteCurrency' => 'EUR',
    'rate' => 0.85
]);
```

### Using Filters

```php
use OpenFinancy\ApiClient\Common\Filter\FilterComposite;
use OpenFinancy\ApiClient\Common\Filter\KeyValueFilter;
use OpenFinancy\ApiClient\Common\Filter\PaginationFilter;

$filters = new FilterComposite(
    new KeyValueFilter('currency', 'USD'),
    new PaginationFilter(page: 1, itemsPerPage: 10)
);

$response = $client->getCollection('exchange-rates', $filters);
```

## API Reference

### ApiPlatformClient

Main HTTP client for API Platform interactions.

#### Methods

- `getCollection(string $resource, ?FilterComponentInterface $filters = null, array $context = []): array`
  - Fetch a collection of resources
  - Supports filtering and pagination via filters
  - Returns decoded JSON response

- `getItem(string $resource, string $id, array $context = []): array`
  - Fetch a single resource by ID
  - Returns decoded JSON response

- `createItem(string $resource, array $payload, array $context = []): array`
  - Create a new resource via POST
  - Returns the created resource

- `updateItem(string $resource, string $id, array $payload, array $context = []): array`
  - Fully replace a resource via PUT
  - Returns the updated resource

- `patchItem(string $resource, string $id, array $payload, array $context = []): array`
  - Partially update a resource via PATCH
  - Returns the updated resource

- `deleteItem(string $resource, string $id, array $context = []): void`
  - Delete a resource via DELETE

### AbstractApiResourceClient

Base class for resource-specific API clients. Provides common CRUD operations.

#### Protected Methods

- `getCollection(?FilterComponentInterface $filters = null, array $context = []): array`
- `getItem(string $id, array $context = []): array`
- `createItem(array $payload, array $context = []): array`
- `updateItem(string $id, array $payload, array $context = []): array`
- `patchItem(string $id, array $payload, array $context = []): array`
- `deleteItem(string $id, array $context = []): void`

#### Abstract Method

- `getResourcePath(): string` - Must be implemented to return the API resource path

### Filter System

The filter system uses the Composite pattern to build complex query parameters.

#### FilterComponentInterface

Interface for all filter components.

```php
interface FilterComponentInterface
{
    public function apply(array $parameters = []): array;
}
```

#### FilterComposite

Combines multiple filter components.

```php
$composite = new FilterComposite(
    new KeyValueFilter('status', 'active'),
    new PaginationFilter(page: 1)
);

$params = $composite->apply([]);
// Returns: ['status' => 'active', 'page' => 1]
```

#### AbstractFilterBuilder

Base class for building filters with common functionality:

- `page(int $page): static` - Add pagination page
- `itemsPerPage(int $itemsPerPage): static` - Add items per page
- `addKeyValue(string $key, mixed $value, bool $allowNull = false): static` - Add key-value filter
- `addOrder(string $field, string $direction): static` - Add ordering
- `addDateFilter(string $field, string $comparison, DateTimeInterface|string $value): static` - Add date filter

### CollectionResponseDTO

Base DTO for API Platform collection responses.

```php
abstract class CollectionResponseDTO
{
    public function __construct(
        public readonly array $items,
        public readonly int $totalItems = 0,
        public readonly ?array $view = null,
        public readonly ?string $nextPageUrl = null,
        public readonly ?string $previousPageUrl = null,
    ) {}

    public static function fromApiResponse(array $response): static;
    public function getItems(): array;
    public function getTotalItems(): int;
    public function hasNextPage(): bool;
    public function hasPreviousPage(): bool;
}
```

### ApiPlatformClientException

Domain-specific exception for API Platform client errors.

```php
final class ApiPlatformClientException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {}

    public function getStatusCode(): ?int;
}
```

## Examples

### Creating a Custom Resource Client

```php
use OpenFinancy\ApiClient\Common\Resource\AbstractApiResourceClient;
use OpenFinancy\ApiClient\Common\ApiPlatformClientInterface;

final class CustomResourceClient extends AbstractApiResourceClient
{
    protected function getResourcePath(): string
    {
        return 'custom-resources';
    }

    public function list(): array
    {
        return $this->getCollection();
    }

    public function get(string $id): array
    {
        return $this->getItem($id);
    }
}

// Usage
$client = new CustomResourceClient($apiPlatformClient);
$items = $client->list();
```

### Building Complex Filters

```php
use OpenFinancy\ApiClient\Common\Filter\AbstractFilterBuilder;
use OpenFinancy\ApiClient\Common\Filter\FilterComposite;

class CustomFilterBuilder extends AbstractFilterBuilder
{
    public function withStatus(string $status): self
    {
        return $this->addKeyValue('status', $status);
    }

    public function withDateRange(DateTimeInterface $start, DateTimeInterface $end): self
    {
        return $this
            ->addDateFilter('createdAt', 'after', $start)
            ->addDateFilter('createdAt', 'before', $end);
    }
}

$filter = (new CustomFilterBuilder())
    ->withStatus('active')
    ->withDateRange($startDate, $endDate)
    ->page(1)
    ->itemsPerPage(20);

$response = $client->getCollection('resources', $filter);
```

## Symfony Integration

### Service Configuration

```yaml
services:
    OpenFinancy\ApiClient\Common\ApiPlatformClient:
        arguments:
            $httpClient: '@http_client'
            $baseUri: '%env(API_BASE_URI)%'
            $cache: '@cache.app'  # Optional
```

## Testing

Run the test suite:

```bash
composer test
```

Or with PHPUnit directly:

```bash
vendor/bin/phpunit
```

## License

EUPL-1.2

## Related Packages

- [api-client-market-rates](../api-client-market-rates) - Market rates API client
- [api-client-crypto-rates](../api-client-crypto-rates) - Cryptocurrency rates API client
- [api-client-collectible-rates](../api-client-collectible-rates) - Collectible rates API client
- [api-client-metal-rates](../api-client-metal-rates) - Metal rates API client
