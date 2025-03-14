<?php


namespace Light\GraphQL;

use GQL\Type\MixedTypeMapperFactory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use TheCodingMachine\GraphQLite\SchemaFactory;

class Server
{

    protected $container;
    protected $cache;
    protected $factory;

    public function __construct($defaultLiftetime = 15)
    {
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
}
