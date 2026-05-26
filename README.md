# Light GraphQL

A lightweight GraphQL server library for PHP 8.3+, built on top of [`thecodingmachine/graphqlite`](https://graphqlite.thecodingmachine.com/).

## Requirements

- PHP 8.3+

## Installation

```bash
composer require mathsgod/light-graphql
```

## Dependencies

| Package | Purpose |
|---|---|
| `thecodingmachine/graphqlite` | GraphQL schema generation via PHP attributes |
| `league/container` | Dependency injection container |
| `symfony/cache` | Schema caching |
| `ecodev/graphql-upload` | File upload support (multipart/form-data) |
| `laminas/laminas-diactoros` | PSR-7 HTTP request/response |

## Usage

### Define a Controller

Use `#[Query]` and `#[Mutation]` attributes from GraphQLite to define your GraphQL fields:

```php
namespace Controllers;

use TheCodingMachine\GraphQLite\Annotations\Query;

class UserController
{
    #[Query]
    public function hello(): string
    {
        return 'world';
    }
}
```

### Execute a Query Directly

```php
require_once __DIR__ . '/vendor/autoload.php';

$server = new \Light\GraphQL\Server();
$server->getSchemaFactory()->addNamespace('Controllers');

$result = $server->executeQuery('{ hello }');
print_r($result->toArray());
```

### Handle a PSR-7 Request

`Server` implements `Psr\Http\Server\RequestHandlerInterface` and supports both `application/json` and `multipart/form-data` (file uploads):

```php
$server = new \Light\GraphQL\Server();
$server->getSchemaFactory()->addNamespace('Controllers');

// $request is a PSR-7 ServerRequestInterface
$response = $server->handle($request);
```

### Constructor Options

```php
$server = new \Light\GraphQL\Server(
    defaultLifetime: 15,   // Schema cache TTL in seconds (default: 15)
    debug: false,          // Enable debug mode (default: false)
    container: null        // Custom PSR-11 container (default: League\Container)
);
```

#### Debug Mode

When `debug: true`, error responses include the exception message and stack trace in the `extensions` field:

```json
{
  "errors": [
    {
      "message": "Something went wrong",
      "extensions": {
        "trace": [...]
      }
    }
  ]
}
```

### Error Responses

All responses follow the standard GraphQL error format and always return HTTP 200:

```json
{
  "errors": [
    { "message": "..." }
  ]
}
```

## API

| Method | Description |
|---|---|
| `getSchemaFactory(): SchemaFactory` | Returns the GraphQLite SchemaFactory for adding namespaces |
| `getContainer(): ContainerInterface` | Returns the DI container |
| `getCache(): CacheInterface` | Returns the PSR-16 cache instance |
| `executeQuery(?string $query, array $variables, ?string $operationName): ExecutionResult` | Execute a GraphQL query directly |
| `handle(ServerRequestInterface $request): ResponseInterface` | Handle a PSR-7 HTTP request |
| `executeGraphQLRequest(ServerRequestInterface $request): ResponseInterface` | Process a pre-parsed PSR-7 request (used internally by `handle()`) |
