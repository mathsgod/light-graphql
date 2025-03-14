<?php

use Controllers\RootController;
use GraphQL\GraphQL;

require_once __DIR__ . '/vendor/autoload.php';

$server = new \Light\GraphQL\Server();

$factory = $server->getSchemaFactory();

$factory->addNamespace("Controllers");

$schema = $factory->createSchema();

print_R(GraphQL::executeQuery($schema, "{test}")->toArray());
