<?php

namespace GearDev\LaravelBridge\HttpServer;

use GearDev\HttpSwowServer\Bridging\HttpCycleInterface;
use GearDev\LaravelBridge\Client\SwowHttpPhpClient;
use Laravel\Octane\ApplicationFactory;
use Laravel\Octane\RequestContext;
use Laravel\Octane\Worker;
use Swow\Psr7\Server\ServerConnection;

class HttpServer implements HttpCycleInterface
{
    private SwowHttpPhpClient $swowClient;
    private Worker $worker;

    public function onServerStart()
    {
        $this->swowClient = new SwowHttpPhpClient();
        $this->worker = tap(
        new Worker(
            new ApplicationFactory(realpath(dirname($GLOBALS['_composer_autoload_path']).'/../')), $this->swowClient
        )
        )->boot();
    }

    public function onRequest(ServerConnection $connection)
    {
        try {
            $requestContext = new RequestContext();
            $requestContext->swowConnection = $connection;
            [$request, $context] = $this->swowClient->marshalRequest($requestContext);

            $this->worker->handle($request, $context);
        } catch (\Throwable $e) {
            report($e);
            $connection->error($e->getCode(), $e->getMessage(), close: true);
            $connection->close();
        }

    }
}