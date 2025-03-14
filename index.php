<?php

use Controllers\RootController;
use GraphQL\GraphQL;

require_once __DIR__ . '/vendor/autoload.php';

$server = new \Light\GraphQL\Server();

$server->getSchemaFactory()->addNamespace("Controllers");

print_R($server->executeQuery("{test}")->toArray());
