<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Tests;

use OpenFinancy\ApiClient\Common\ApiPlatformClient;
use OpenFinancy\ApiClient\Common\Exception\ApiPlatformClientException;
use OpenFinancy\ApiClient\Common\Filter\FilterComponentInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ApiPlatformClientTest extends TestCase
{
    public function testGetCollectionReturnsDecodedContent(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $filter = $this->createMock(FilterComponentInterface::class);

        $filter->expects(self::once())
            ->method('apply')
            ->with([])
            ->willReturn(['symbol' => 'BTC']);

        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getContent')->with(false)->willReturn(json_encode(['hydra:member' => []], \JSON_THROW_ON_ERROR));

        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.example/crypto-prices', [
                'headers' => ['Accept' => 'application/ld+json'],
                'query' => ['symbol' => 'BTC'],
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        self::assertSame(['hydra:member' => []], $client->getCollection('crypto-prices', $filter));
    }

    public function testGetCollectionThrowsOnHttpError(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(502);
        $response->method('getContent')->willReturn('error');

        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.example/crypto-prices', [
                'headers' => ['Accept' => 'application/ld+json'],
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $this->expectException(ApiPlatformClientException::class);
        $this->expectExceptionMessage('failed with status 502');

        $client->getCollection('crypto-prices');
    }

    public function testConstructorRejectsEmptyBaseUri(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);

        $this->expectException(ApiPlatformClientException::class);
        $this->expectExceptionMessage('API Platform base URI cannot be empty.');

        new ApiPlatformClient($httpClient, '   ');
    }

    public function testCreateItemSetsCorrectContentType(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $payload = ['name' => 'Test Resource'];

        $response->expects(self::once())->method('getStatusCode')->willReturn(201);
        $response->expects(self::once())->method('getContent')->with(false)->willReturn(json_encode(['@id' => '/resources/1', '@type' => 'Resource'], \JSON_THROW_ON_ERROR));

        $httpClient->expects(self::once())
            ->method('request')
            ->with('POST', 'https://api.example/resources', [
                'headers' => [
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/ld+json',
                ],
                'json' => $payload,
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $result = $client->createItem('resources', $payload);
        self::assertSame(['@id' => '/resources/1', '@type' => 'Resource'], $result);
    }

    public function testUpdateItemSetsCorrectContentType(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $payload = ['name' => 'Updated Resource'];

        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getContent')->with(false)->willReturn(json_encode(['@id' => '/resources/1', '@type' => 'Resource'], \JSON_THROW_ON_ERROR));

        $httpClient->expects(self::once())
            ->method('request')
            ->with('PUT', 'https://api.example/resources/1', [
                'headers' => [
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/ld+json',
                ],
                'json' => $payload,
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $result = $client->updateItem('resources', '1', $payload);
        self::assertSame(['@id' => '/resources/1', '@type' => 'Resource'], $result);
    }

    public function testPatchItemSetsCorrectContentType(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $payload = ['name' => 'Patched Resource'];

        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getContent')->with(false)->willReturn(json_encode(['@id' => '/resources/1', '@type' => 'Resource'], \JSON_THROW_ON_ERROR));

        $httpClient->expects(self::once())
            ->method('request')
            ->with('PATCH', 'https://api.example/resources/1', [
                'headers' => [
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => $payload,
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $result = $client->patchItem('resources', '1', $payload);
        self::assertSame(['@id' => '/resources/1', '@type' => 'Resource'], $result);
    }

    public function testDeleteItemSendsDeleteRequestAndHandlesEmptyResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->expects(self::once())->method('getStatusCode')->willReturn(204);
        $response->expects(self::once())->method('getContent')->with(false)->willReturn('');

        $httpClient->expects(self::once())
            ->method('request')
            ->with('DELETE', 'https://api.example/resources/1', [
                'headers' => ['Accept' => 'application/ld+json'],
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        // Should not throw
        $client->deleteItem('resources', '1');
        $this->addToAssertionCount(1);
    }

    public function testBuildUriValidatesResourceAndId(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $this->expectException(ApiPlatformClientException::class);
        $this->expectExceptionMessage('Resource path cannot be empty.');
        (new ReflectionClass($client))
            ->getMethod('buildUri')
            ->invoke($client, '');
    }

    public function testGetCollectionHandlesShortJsonLdFormat(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        // Short JSON-LD format uses "member" instead of "hydra:member"
        $shortJsonLdResponse = [
            '@context' => '/contexts/Entrypoint',
            'member' => [
                ['@id' => '/resources/1', 'name' => 'Resource 1'],
                ['@id' => '/resources/2', 'name' => 'Resource 2'],
            ],
            'totalItems' => 2,
        ];

        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getContent')->with(false)->willReturn(json_encode($shortJsonLdResponse, \JSON_THROW_ON_ERROR));

        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.example/resources', [
                'headers' => ['Accept' => 'application/ld+json'],
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $result = $client->getCollection('resources');
        // Verify that short JSON-LD format is properly decoded
        self::assertArrayHasKey('member', $result);
        self::assertArrayHasKey('totalItems', $result);
        self::assertCount(2, $result['member']);
    }

    public function testGetItemHandlesShortJsonLdFormat(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        // Short JSON-LD format may use "id" instead of "@id" in some contexts
        $shortJsonLdResponse = [
            '@context' => '/contexts/Resource',
            'id' => '/resources/1',
            'name' => 'Test Resource',
        ];

        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())->method('getContent')->with(false)->willReturn(json_encode($shortJsonLdResponse, \JSON_THROW_ON_ERROR));

        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.example/resources/1', [
                'headers' => ['Accept' => 'application/ld+json'],
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $result = $client->getItem('resources', '1');
        // Verify that short JSON-LD format is properly decoded
        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('name', $result);
        self::assertSame('/resources/1', $result['id']);
    }
}
