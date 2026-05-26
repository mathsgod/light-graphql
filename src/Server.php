<?php


namespace Light\GraphQL;

use Exception;
use GQL\Type\MixedTypeMapperFactory;
use GraphQL\Error\DebugFlag;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Upload\UploadMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use League\Container\Container as LeagueContainer;
use League\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TheCodingMachine\GraphQLite\SchemaFactory;

class Server implements RequestHandlerInterface
{

    protected ContainerInterface $container;
    protected CacheInterface $cache;
    protected SchemaFactory $factory;
    protected bool $debug;

    public function __construct(int $defaultLifetime = 15, bool $debug = false, ?ContainerInterface $container = null)
    {
        $this->debug = $debug;

        if ($container) {
            $this->container = $container;
        } else {
            $leagueContainer = new LeagueContainer();
            $leagueContainer->delegate(new ReflectionContainer());
            $this->container = $leagueContainer;
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

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function executeQuery(?string $query, array $variables = [], ?string $operationName = null): ExecutionResult
    {
        return GraphQL::executeQuery($this->factory->createSchema(), $query, null, null, $variables, $operationName);
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
            return $this->buildErrorResponse($e);
        }
    }

    public function executeGraphQLRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Use pre-parsed body (e.g. from UploadMiddleware) or decode JSON body
        $body = $request->getParsedBody()
            ?? json_decode($request->getBody()->getContents(), true);

        if (!is_array($body)) {
            return new JsonResponse(['errors' => [['message' => 'Invalid request body.']]], 200);
        }

        $query = $body['query'] ?? null;
        $variables = is_array($body['variables'] ?? []) ? ($body['variables'] ?? []) : [];
        $operationName = $body['operationName'] ?? null;

        if ($query === null) {
            return new JsonResponse(['errors' => [['message' => 'Missing query in request.']]], 200);
        }

        try {
            $result = $this->executeQuery($query, $variables, $operationName);
            if ($this->debug) {
                $data = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
            } else {
                $data = $result->toArray();
            }

            return new JsonResponse($data, 200, [], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            return $this->buildErrorResponse($e);
        }
    }

    private function buildErrorResponse(Exception $e): ResponseInterface
    {
        $error = $this->debug
            ? ['message' => $e->getMessage(), 'extensions' => ['trace' => $e->getTrace()]]
            : ['message' => 'An internal server error occurred.'];

        return new JsonResponse(['errors' => [$error]], 200);
    }
}
