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

    protected $container;
    protected $cache;
    protected $factory;
    protected $debug;

    public function __construct($defaultLiftetime = 15, $debug = false, ?ContainerInterface $container)
    {
        $this->debug = $debug;

        if ($container) {
            $this->container = $container;
        } else {
            $this->container = new \League\Container\Container();
            $this->container->delegate(new \League\Container\ReflectionContainer());
        }

        $this->cache = new Psr16Cache(new FilesystemAdapter("light", $defaultLiftetime));

        $this->factory = new SchemaFactory($this->cache, $this->container);

        $this->factory->addRootTypeMapperFactory(new MixedTypeMapperFactory);
        $this->factory->prodMode();
    }

    public function getSchemaFactory()
    {
        return $this->factory;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function executeQuery($query, $variables = [], $operationName = null)
    {
        return \GraphQL\GraphQL::executeQuery($this->factory->createSchema(), $query, null, null, $variables, $operationName);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withParsedBody(
            json_decode($request->getBody()->getContents(), true)
        );
        $uploadMiddleware = new UploadMiddleware();
        $request = $uploadMiddleware->processRequest($request);

        $body = $request->getParsedBody();
        $query = $body["query"];
        $variables = $body["variables"] ?? [];

        try {
            $result = $this->executeQuery($query, $variables);
            if ($this->debug) {
                $result = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
            } else {
                $result = $result->toArray();
            }

            return new JsonResponse($result);
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
