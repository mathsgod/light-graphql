<?php


namespace Light\GraphQL;

use Exception;
use GQL\Type\MixedTypeMapperFactory;
use GraphQL\Error\DebugFlag;
use GraphQL\Upload\UploadMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TheCodingMachine\GraphQLite\SchemaFactory;

class Server implements RequestHandlerInterface
{

    protected ContainerInterface $container;
    protected \Psr\SimpleCache\CacheInterface $cache;
    protected SchemaFactory $factory;
    protected bool $debug;

    public function __construct(int $defaultLifetime = 15, bool $debug = false, ?ContainerInterface $container = null)
    {
        $this->debug = $debug;

        if ($container) {
            $this->container = $container;
        } else {
            $this->container = new \League\Container\Container();
            $this->container->delegate(new \League\Container\ReflectionContainer());
        }

        $this->cache = new Psr16Cache(new FilesystemAdapter("light", $defaultLifetime));

        $this->factory = new SchemaFactory($this->cache, $this->container);

        $this->factory->addRootTypeMapperFactory(new MixedTypeMapperFactory);
        $this->factory->prodMode();
    }

    public function getSchemaFactory(): SchemaFactory
    {
        return $this->factory;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getCache(): \Psr\SimpleCache\CacheInterface
    {
        return $this->cache;
    }

    public function executeQuery(string $query, array $variables = [], ?string $operationName = null): \GraphQL\Executor\ExecutionResult
    {
        return \GraphQL\GraphQL::executeQuery($this->factory->createSchema(), $query, null, null, $variables, $operationName);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uploadMiddleware = new UploadMiddleware();

        $inner = new class($this) implements RequestHandlerInterface {
            public function __construct(private Server $server) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->server->executeGraphQLRequest($request);
            }
        };

        try {
            return $uploadMiddleware->process($request, $inner);
        } catch (Exception $e) {
            if ($this->debug) {
                $errorResponse = [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ];
            } else {
                $errorResponse = [
                    'error' => true,
                    'message' => 'An internal server error occurred.',
                ];
            }
            return new JsonResponse($errorResponse, 500);
        }
    }

    public function executeGraphQLRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Use pre-parsed body (e.g. from UploadMiddleware) or decode JSON body
        $body = $request->getParsedBody()
            ?? json_decode($request->getBody()->getContents(), true);

        $query = $body['query'] ?? null;
        $variables = $body['variables'] ?? [];
        $operationName = $body['operationName'] ?? null;

        try {
            $result = $this->executeQuery($query, $variables, $operationName);
            if ($this->debug) {
                $data = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
            } else {
                $data = $result->toArray();
            }

            return new JsonResponse($data, 200, [], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            if ($this->debug) {
                $errorResponse = [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ];
            } else {
                $errorResponse = [
                    'error' => true,
                    'message' => 'An internal server error occurred.',
                ];
            }
            return new JsonResponse($errorResponse, 500);
        }
    }
}
