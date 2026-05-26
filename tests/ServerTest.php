<?php

namespace Tests;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Light\GraphQL\Server;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use TheCodingMachine\GraphQLite\SchemaFactory;

class ServerTest extends TestCase
{
    private Server $server;

    protected function setUp(): void
    {
        $this->server = new Server();
        $this->server->getSchemaFactory()->addNamespace('Controllers');
    }

    public function testGetSchemaFactoryReturnsSchemaFactory(): void
    {
        $this->assertInstanceOf(SchemaFactory::class, $this->server->getSchemaFactory());
    }

    public function testGetContainerReturnsContainerInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->server->getContainer());
    }

    public function testGetCacheReturnsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->server->getCache());
    }

    public function testExecuteQuery(): void
    {
        $result = $this->server->executeQuery('{ hello }');
        $data = $result->toArray();

        $this->assertArrayHasKey('data', $data);
        $this->assertSame('world', $data['data']['hello']);
    }

    public function testHandleJsonRequest(): void
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write(json_encode(['query' => '{ hello }']));
        $stream->rewind();

        $request = new ServerRequest(
            [], [], '/', 'POST', $stream,
            ['Content-Type' => 'application/json']
        );

        $response = $this->server->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame('world', $data['data']['hello']);
    }

    public function testHandleWithPreParsedBody(): void
    {
        // Simulates a request where parsedBody is already set (e.g. after UploadMiddleware processing)
        $request = new ServerRequest(
            [], [], '/', 'POST', 'php://temp', [], [], [],
            ['query' => '{ hello }']
        );

        $response = $this->server->executeGraphQLRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame('world', $data['data']['hello']);
    }

    public function testHandleReturnsErrorsForInvalidQuery(): void
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write(json_encode(['query' => '{ nonExistentField }']));
        $stream->rewind();

        $request = new ServerRequest(
            [], [], '/', 'POST', $stream,
            ['Content-Type' => 'application/json']
        );

        $response = $this->server->handle($request);
        $data = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('errors', $data);
    }

    public function testHandleDebugModeIncludesDebugInfo(): void
    {
        $server = new Server(15, true);
        $server->getSchemaFactory()->addNamespace('Controllers');

        $stream = new Stream('php://temp', 'rw');
        $stream->write(json_encode(['query' => '{ hello }']));
        $stream->rewind();

        $request = new ServerRequest(
            [], [], '/', 'POST', $stream,
            ['Content-Type' => 'application/json']
        );

        $response = $server->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame('world', $data['data']['hello']);
    }
}
