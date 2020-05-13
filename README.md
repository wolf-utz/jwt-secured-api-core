![alt text](https://travis-ci.org/0m3gaC0d3/jwt-secured-api-core.svg?branch=master "Build status")

# Simple JWT secured API Core
This is the core component of the simple jwt based API skeleton to kick start your API development.
It is based on the PHP micro framework [Slim 4](http://www.slimframework.com/)
 and some well known [Symfony 5](https://symfony.com/) components.

The skeleton comes also bundled with [DI (dependency injection)](https://symfony.com/doc/current/components/dependency_injection.html)
 and [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.10/index.html).

## Requirements

* PHP 7.4+
* composer
* openssl
* PHP extension ext-json

## Application workflow / design

This framework uses the [ADR](https://wikipedia.org/wiki/Action%E2%80%93domain%E2%80%93responder) 
pattern to provide API endpoints. This means: each endpoint and HTTP method results in a single action file.

The framework provides an easy workflow to create robust and scalable REST-APIs (There is even GraphQL support). 

Almost every part of the framework is extendable by using symfony events, service decoration or middlewares.

### Http kernel

For each HTTP based request the HTTTP-Kernel is used to bootstrap the application. it provides DI, 
the underlaying slim framework and route configurations.

### CLI kernel

The CLI-Kernel is used to bootstrap symfony commands or testing frameworks. 
It bootstraps the application without the HTTP components (Slim, Routes, etc.).

## Setup

### Add the core
First require the core `omegacode/jwt-secured-api-core`.

### Define your configuration path
In your composer json add an extra section, which points to your configuration folder.
````json
...
    "extra": {
        "jwt-secured-api": {
            "conf-dir": "path/to/conf/directory"
        }
    }
...
````

The configuration folder should contain two `.yml` files.
* `routes.yaml`
* `services.yaml`

### Add your environment variables

Next you need to provide some environment variables. 
This can be archived by copying the `.env.dist` file of the core and save it as `.env` in the root of your project.
Adjust each value of the file to your needs.

### Build

Finally you have to build your project by running  `composer install`.

### Create public and private key for JWT

Run `bin/console api:keys:generate` to generate the keys.

## How to add endpoints / routes

To provide endpoints you have to define one or more routes. This happens in your `routes.yaml`.

An example route looks like this:
````yaml
routes:
  -
    name: home
    route: /
    methods: [get]
    action: App\Action\Standard\WelcomeAction
    middlewares:
      - OmegaCode\JwtSecuredApiCore\Middleware\CacheableHTMLMiddleware
````

* `name`: Your route needs an unique name (This will be used as an internal identifier).
* `route`: Also the actual endpoint / URL of your route must be provided
(Check out the [Slim Documentation](http://www.slimframework.com/docs/v4/objects/routing.html) the see all possibilities).
* `methods`: Next you can provide one or more HTTP-Methods to listen to. Other methods calling this endpoint resulting in 400 errors.
* `action`: This is the actual implementation of your action class. Simple enter the FQCN of your action.
* `middlewares`: Here you can provide middlewares to extend the request behavior. 
To protect your route with JWT, add the following middle ware: `OmegaCode\JwtSecuredApiCore\Middleware\JsonWebTokenMiddleware`.
There are also middlewares for caching HTML or JSON responses (`OmegaCode\JwtSecuredApiCore\Middleware\CacheableJSONMiddleware` 
and `OmegaCode\JwtSecuredApiCore\Middleware\CacheableHTMLMiddleware`.

### Create an action

The action class is used to handle the request to a single route.

An action can look like this:
````php
<?php

declare(strict_types=1);

namespace App\Action\Auth;

use OmegaCode\JwtSecuredApiCore\Action\AbstractAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AwesomeAction extends AbstractAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $data = [
            "data" => "Awesome data!"
        ];
        $response->getBody()->write((string) json_encode($data));
        $response = $response->withStatus(201)->withHeader('Content-type', 'application/json');

        return $response;
    }
}
````

Note that your action must extend `OmegaCode\JwtSecuredApiCore\Action\AbstractAction`.

## How to modify existing routes

In some circumstances you may need to modify or remove an existing route. This can be archived by subscribing to an event.
Your subscriber can look like the following:

````php
<?php

declare(strict_types=1);

namespace App\Subscriber;

use OmegaCode\JwtSecuredApiCore\Event\RouteCollectionFilledEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RouteSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RouteCollectionFilledEvent::NAME => 'onFilled',
        ];
    }

    public function onFilled(RouteCollectionFilledEvent $event): void
    {
        $routeCollection = $event->getCollection();
        $routeToRemove = $routeCollection->findByName('myRouteToRemove');
        $routeCollection->remove($routeToRemove);
    }
}
````

Remember to introduce your subscriber to your `service.yml`.

````yaml
services:
  ...
  App\Subscriber\RouteSubscriber:
    tags:
      - 'kernel.event_subscriber'
  ...
````

## How to add new commands

You can create CLI-Commands or better said [symfony commands](https://symfony.com/doc/current/components/console.html)
by providing a command class and an entry in your `service.yml`, which allows DI in your command.

An example can look like:
````php
<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AwesomeCommand extends Command
{
    protected static $defaultName = 'app:awesome';

    protected function configure(): void
    {
        $this->setDescription('This command prints "awesome" to the console');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        echo "Awesome!".PHP_EOL;

        return 0;
    }
}
````

And your `service.yml` entry could look like:
````yaml
services:
  ...
  App\Command\AwesomeCommand:
    tags:
      - { name: 'console.command', command: 'app:awesome' }
  ...
````

## Some words about the cache

The framework caches some work to increase the response speed of your API.

The following is a list of all cached parts of the core
* `Request cache`: You can cache a request by providing cacheable middlewares 
(`OmegaCode\JwtSecuredApiCore\Middleware\JsonWebTokenMiddleware`, `OmegaCode\JwtSecuredApiCore\Middleware\CacheableJSONMiddleware`) 
to your route configuration. 
Remember to enable that feature by setting the environment variable `ENABLE_REQUEST_CACHE` to `1`

* `System cache`: The system cache stores the configuration directories of each package related to the core. 
This process is really expensive in terms of time. 
For debugging purposes you can disable this cache by setting `ENABLE_SYSTEM_CACHE` to `1`.

### Cache options

The `.env` file contains two more variables about caching

* `CACHE_ADAPTER_CLASS`: The framework uses the 
[symfony cache component](https://symfony.com/doc/current/components/cache.html) to cache data. 
This enables you to define the actual storage of the cache (File, APCu, Redis...). 
To change the actual cache implementation of the core, this environment variable is given. Simple change the FQCN with the one you need.
(*This is going to change soon*) 
* `REQUEST_CACHE_LIFE_TIME`: This environment variable is used to define the live time of the request cache.
(*This is going to change soon*)

### How to clear the cache

To clear the cache of the application a command can be used (`bin/console cache:clear`).

## Auth / JWT

The core comes with [JWT](https://wikipedia.org/wiki/JSON_Web_Token) support. 
To enable a JWT secured rout simply add the following middleware: 
`OmegaCode\JwtSecuredApiCore\Middleware\JsonWebTokenMiddleware`

To create the signature a private and a public key is needed.

You clients need to navigate to `/auth` to obtain a JWT by providing a valid client ID.

### How to define valid client ids

Currently client ids are stored in the environment variable `CLIENT_IDS` (comma separated list). 
(*This is going to change soon*)

### How to obtain a token

To obtain tokens there is a route given by the core `/auth`

The response of this request contains a json object like the following:
````json
{
    "access_token": "...",
    "token_type": "Bearer",
    "expires_in": 900
}
````

### How to verify a token

To verify a token you can navigate to `/auth/verify`. Remember to add the token to validate in the Authorization header.

The response of this request contains a json object like the following:
````json
{
    "success": true
}
````

## Extendability

One of the main goals of this framework is extendability. To archive this well known systems have been integrated.

### Symfony events

[Symfony events](https://symfony.com/doc/current/components/event_dispatcher.html) are used to extend the system without
 using something like subclassing or other methods. A good example for core usage is the route collection.
 
Following a list of all existing events:
* `route_collection.filled`: This event is used to manipulate routes.
* `request.pre`: This event is used to manipulate the request / response before the action executes
* `request.post`: This event is used to manipulate the request / response after the action executes
* `kernel.pre`: This event is triggered early in the process of building the HTTP kernel.
* `kernel.post`: This event is triggered directly before Slim kicks in.

### Middlewares

* `OmegaCode\JwtSecuredApiCore\Middleware\JsonWebTokenMiddleware`: Secures your routes with JWT.
* `OmegaCode\JwtSecuredApiCore\Middleware\CacheableJSONMiddleware`: Caches your response as JSON.
* `OmegaCode\JwtSecuredApiCore\Middleware\CacheableHTMLMiddleware`: Caches your response as HTML.

### DI

[Symfony Dependency injection](https://symfony.com/doc/current/components/dependency_injection.html) provides a way 
to extend the core. You can [decorate](https://symfony.com/doc/current/service_container/service_decoration.html) 
any service (Note that each action is also a service) to add custom code.

## Logging

To enable logging simply set the environment variable `ENABLE_LOG` to `1`. The log currently only logs API errors.

## Errors

Each error comes wrapped in JSON to ensure a better/consistent API experience for your clients.

## GraphQL

If you want to use [GraphQL](https://wikipedia.org/wiki/GraphQL), 
simply run  `composer require omegacode/jwt-secured-api-graphql`. 
Read the documentation of the project for more information.
