# Light GraphQL

Light GraphQL is a lightweight GraphQL server implementation designed for simplicity and ease of use. This project demonstrates how to set up a basic GraphQL server using PHP.

## Installation

```
composer require light/graphql
```

### Key Components

- **Schema Factory**: The schema is dynamically created using the `SchemaFactory` provided by the `Light\GraphQL\Server`. It allows adding namespaces for controllers.
- **Controllers**: The `Controllers` namespace is used to define resolvers for GraphQL queries.

- **GraphQL Execution**: The `GraphQL::executeQuery` method is used to execute the GraphQL query against the schema.

### Code Overview


```php
use Controllers\RootController;
use GraphQL\GraphQL;

require_once __DIR__ . '/vendor/autoload.php';

$server = new \Light\GraphQL\Server();
$server->getSchemaFactory()->addNamespace("Controllers");

print_R($sever->executeQuery($query)->toArray());
```
