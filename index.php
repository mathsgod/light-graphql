<?php

use GraphQL\GraphQL;

require_once __DIR__ . '/vendor/autoload.php';

$server = new \Light\GraphQL\Server();



$schema = $server->getSchemaFactory()->createSchema();


print_R(GraphQL::executeQuery($schema, "{hello}")->toArray());
