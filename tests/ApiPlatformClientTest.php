<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Tests;

use OpenFinancy\ApiClient\Common\ApiPlatformClient;
use OpenFinancy\ApiClient\Common\Exception\ApiPlatformClientException;
use OpenFinancy\ApiClient\Common\Filter\FilterComponentInterface;
use PHPUnit\Framework\TestCase;
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
        $response->expects(self::once())->method('getContent')->with(false)->willReturn(json_encode(['hydra:member' => []], JSON_THROW_ON_ERROR));

        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.example/crypto-prices', [
                'headers' => ['Accept' => 'application/json'],
                'query' => ['symbol' => 'BTC'],
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        self::assertSame(['hydra:member' => []], $client->getCollection('crypto-prices', $filter));
    }

    public function testGetCollectionThrowsOnHttpError(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')->willReturn(502);
        $response->method('getContent')->with(false)->willReturn('error');

        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.example/crypto-prices', [
                'headers' => ['Accept' => 'application/json'],
            ])
            ->willReturn($response);

        $client = new ApiPlatformClient($httpClient, 'https://api.example');

        $this->expectException(ApiPlatformClientException::class);
        $this->expectExceptionMessage('failed with status 502');

        $client->getCollection('crypto-prices');
    }

    public function testConstructorRejectsEmptyBaseUri(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);

        $this->expectException(ApiPlatformClientException::class);
        $this->expectExceptionMessage('API Platform base URI cannot be empty.');

        new ApiPlatformClient($httpClient, '   ');
    }
}
