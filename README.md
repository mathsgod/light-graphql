# Light GraphQL

Light GraphQL is a lightweight GraphQL server implementation designed for simplicity and ease of use. This project demonstrates how to set up a basic GraphQL server using PHP.

This library is built using several dependencies, including `thecodingmachine/graphqlite`, which provides annotations and tools for creating GraphQL APIs in PHP. Other key dependencies include:

- **league/container**: A lightweight dependency injection container.
- **symfony/cache**: A caching library for optimizing performance.

These libraries work together to provide a robust and flexible foundation for building GraphQL servers.

## Installation

```
composer require light/graphql
```

### Key Components

- **Schema Factory**: The schema is dynamically created using the `SchemaFactory` provided by the `Light\GraphQL\Server`. It allows adding namespaces for controllers.
- **Controllers**: The `Controllers` namespace is used to define resolvers for GraphQL queries.

- **GraphQL Execution**: The `Server::executeQuery` method is used to execute the GraphQL query against the schema. In the provided example, the query `$query` is executed, and the result is converted to an array using `toArray()`.

### Usage Example


```php
use Controllers\RootController;
use GraphQL\GraphQL;

require_once __DIR__ . '/vendor/autoload.php';

$server = new \Light\GraphQL\Server();
$server->getSchemaFactory()->addNamespace("Controllers");

print_R($sever->executeQuery($query)->toArray());
```


#### ServerRequestInterface handling

```php

use Controllers\RootController;
use GraphQL\GraphQL;

$server = new \Light\GraphQL\Server();
$server->getSchemaFactory()->addNamespace("Controllers");

$response=$server->handle($request);
