<?php

/**
 * MIT License
 *
 * Copyright (c) 2020 Wolf Utz<wpu@hotmail.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace OmegaCode\JwtSecuredApiCore;

use OmegaCode\JwtSecuredApiCore\Auth\JsonWebTokenAuth;
use OmegaCode\JwtSecuredApiCore\Configuration\Processor\RouteConfigurationProcessor;
use OmegaCode\JwtSecuredApiCore\Event\Request\PostRequestEvent;
use OmegaCode\JwtSecuredApiCore\Event\Request\PreRequestEvent;
use OmegaCode\JwtSecuredApiCore\Event\RouteCollectionFilledEvent;
use OmegaCode\JwtSecuredApiCore\Factory\Route\CollectionFactory;
use OmegaCode\JwtSecuredApiCore\Route\Configuration;
use OmegaCode\JwtSecuredApiCore\Service\ConfigurationFileService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App as API;
use Slim\Interfaces\RouteInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Router
{
    public const ALLOWED_METHODS = ['get', 'post', 'put', 'delete', 'patch'];

    private API $api;

    private ConfigurationFileService $configurationFileService;

    private JsonWebTokenAuth $auth;

    private EventDispatcher $eventDispatcher;

    public function __construct(
        API $api,
        ConfigurationFileService $configurationFileService,
        JsonWebTokenAuth $auth,
        EventDispatcher $eventDispatcher
    ) {
        $this->api = $api;
        $this->configurationFileService = $configurationFileService;
        $this->auth = $auth;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function registerRoutes(ContainerInterface $container): void
    {
        $configuration = $this->configurationFileService->load('routes.yaml');
        $routeCollection = CollectionFactory::build(
            $container,
            (new RouteConfigurationProcessor())->process($configuration)
        );
        $this->eventDispatcher->dispatch(
            new RouteCollectionFilledEvent($routeCollection),
            RouteCollectionFilledEvent::NAME
        );
        foreach ($routeCollection as $routeConfig) {
            $this->handleRequest($routeConfig);
        }
    }

    private function handleRequest(Configuration $config): void
    {
        /** @var string $method */
        foreach ($config->getAllowedMethods() as $method) {
            $action = $config->getAction();
            $eventDispatcher = $this->eventDispatcher;
            /** @var RouteInterface $router */
            $router = $this->api->$method(
                $config->getRoute(),
                function (Request $request, Response $response) use ($action, $eventDispatcher) {
                    $eventDispatcher->dispatch(new PreRequestEvent($request, $response), PreRequestEvent::NAME);
                    $response = $action($request, $response);
                    $eventDispatcher->dispatch(new PostRequestEvent($request, $response), PostRequestEvent::NAME);

                    return $response;
                }
            );
            $this->addMiddlewares($router, $config->getMiddlewares());
        }
    }

    private function addMiddlewares(RouteInterface $router, array $middlewares): void
    {
        if (count($middlewares) === 0) {
            return;
        }
        /** @var string $middleware */
        foreach ($middlewares as $middleware) {
            $router->add($middleware);
        }
    }
}