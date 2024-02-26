<?php

declare(strict_types=1);

namespace Rinha;

use DI\Container;
use Rinha\Exception\HttpException;

class Application
{
    private static ?Application $instance = null;

    private Container $container;

    private $dispatcher;

    public function __construct()
    {
        $this->container = new Container([
            ConnectionPool::class => new ConnectionPool(),
        ]);

        $this->dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $route) {
            $r = include __DIR__.'/routes.php';
            $r($route);
        });
    }

    public static function getInstance(): Application
    {
        if (self::$instance == null) {
            self::$instance = new Application();
        }

        return self::$instance;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function dispatch(
        \Swoole\Http\Request $request,
        \Swoole\Http\Response $response
    ): void {
        $requestMethod = $request->server['request_method'];
        $requestUri = $request->server['request_uri'];

        $routeInfo = $this->dispatcher->dispatch($requestMethod, $requestUri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                $response->status(404);
                $response->end('Not Found');
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $response->status(405);
                $response->end('Method Not Allowed');
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $action = $this->container->get($handler);

                try {
                    $body = $action->handle($request->rawContent(), $vars);
                    $response->status(200);
                    $response->end($body);
                } catch (HttpException $e) {
                    $response->status($e->getCode());
                    $response->end(json_encode([
                        'status' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ]));
                } catch (\Exception $e) {
                    $response->status(500);
                    $response->end(json_encode([
                        'status' => 500,
                        'message' => 'Erro do servidor',
                    ]));
                }
                break;
        }
    }
}
