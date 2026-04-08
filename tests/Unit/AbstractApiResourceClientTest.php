<?php

declare(strict_types=1);

namespace OpenFinancy\ApiClient\Common\Tests\Unit;

use OpenFinancy\ApiClient\Common\ApiPlatformClientInterface;
use OpenFinancy\ApiClient\Common\Exception\ApiPlatformClientException;
use OpenFinancy\ApiClient\Common\Filter\FilterComponentInterface;
use OpenFinancy\ApiClient\Common\Resource\AbstractApiResourceClient;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
final class AbstractApiResourceClientTest extends TestCase
{
    public function testDelegatesCollectionAndItemOperationsToClient(): void
    {
        $client = $this->createMock(ApiPlatformClientInterface::class);
        $filters = $this->createMock(FilterComponentInterface::class);

        $client->expects(self::once())
            ->method('getCollection')
            ->with('test-resources', $filters, ['foo' => 'bar'])
            ->willReturn(['items' => []]);

        $client->expects(self::once())
            ->method('getItem')
            ->with('test-resources', '123', ['ctx' => true])
            ->willReturn(['id' => '123']);

        $client->expects(self::once())
            ->method('createItem')
            ->with('test-resources', ['name' => 'New'], [])
            ->willReturn(['id' => 'created']);

        $client->expects(self::once())
            ->method('updateItem')
            ->with('test-resources', '123', ['name' => 'Updated'], [])
            ->willReturn(['id' => '123', 'name' => 'Updated']);

        $client->expects(self::once())
            ->method('patchItem')
            ->with('test-resources', '123', ['name' => 'Patched'], [])
            ->willReturn(['id' => '123', 'name' => 'Patched']);

        $client->expects(self::once())
            ->method('deleteItem')
            ->with('test-resources', '123', ['hard' => true]);

        $resource = new class($client) extends AbstractApiResourceClient {
            protected function getResourcePath(): string
            {
                return 'test-resources';
            }

            public function list(?FilterComponentInterface $filters = null, array $context = []): array
            {
                return $this->getCollection($filters, $context);
            }

            public function get(string $id, array $context = []): array
            {
                return $this->getItem($id, $context);
            }

            public function create(array $payload, array $context = []): array
            {
                return $this->createItem($payload, $context);
            }

            public function update(string $id, array $payload, array $context = []): array
            {
                return $this->updateItem($id, $payload, $context);
            }

            public function patch(string $id, array $payload, array $context = []): array
            {
                return $this->patchItem($id, $payload, $context);
            }

            public function delete(string $id, array $context = []): void
            {
                $this->deleteItem($id, $context);
            }
        };

        self::assertSame(['items' => []], $resource->list($filters, ['foo' => 'bar']));
        self::assertSame(['id' => '123'], $resource->get('123', ['ctx' => true]));
        self::assertSame(['id' => 'created'], $resource->create(['name' => 'New']));
        self::assertSame(['id' => '123', 'name' => 'Updated'], $resource->update('123', ['name' => 'Updated']));
        self::assertSame(['id' => '123', 'name' => 'Patched'], $resource->patch('123', ['name' => 'Patched']));

        $resource->delete('123', ['hard' => true]);
    }

    public function testApiPlatformClientExceptionExposesStatusCode(): void
    {
        $previous = new RuntimeException('root cause');
        $exception = new ApiPlatformClientException('failed', 502, $previous);

        self::assertSame('failed', $exception->getMessage());
        self::assertSame(502, $exception->getStatusCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
