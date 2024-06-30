<?php

namespace GearDev\LaravelBridge;

use GearDev\Collector\Listeners\CacheClearListener;
use GearDev\HttpSwowServer\Container\HttpSwowContainer;
use GearDev\LaravelBridge\HttpServer\HttpServer;
use GearDev\Prometheus\Container\RegistryContainer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class LaravelBridgeServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (class_exists(RegistryContainer::class)) {
            $this->app->instance(CollectorRegistry::class, RegistryContainer::getRegistry());
        }
        Event::listen('cache:clearing', CacheClearListener::class);
    }

    public function boot()
    {
        Route::get('/metrics', function () {
            $registry = RegistryContainer::getRegistry();
            $renderer = new RenderTextFormat();
            $responseText = $renderer->render($registry->getMetricFamilySamples());
            return response($responseText)->header('Content-Type', RenderTextFormat::MIME_TYPE);
        });
    }
}
