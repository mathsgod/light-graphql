<?php


namespace Light\GraphQL;

use Exception;
use GQL\Type\MixedTypeMapperFactory;
use GraphQL\Error\DebugFlag;
use GraphQL\Upload\UploadMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
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

    public function __construct($defaultLiftetime = 15)
    {
        $this->container = new \League\Container\Container();
        $this->container->delegate(new \League\Container\ReflectionContainer());

        $this->cache = new Psr16Cache(new FilesystemAdapter(defaultLifetime: $defaultLiftetime));

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
            $result = $this->executeQuery($query, $variables)->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);
            return new JsonResponse($result);
        } catch (Exception $e) {
            return new TextResponse($e->getMessage());
        }
    }
}
