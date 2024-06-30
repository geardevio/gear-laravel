<?php

namespace GearDev\LaravelBridge\Starter;

use GearDev\Collector\Collector\Collector;
use GearDev\Core\Attributes\Clutch;
use GearDev\Core\Attributes\Warmer;
use GearDev\Core\ContextStorage\ContextStorage;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use ReflectionObject;

class Ignition
{
    private function bootEngine(string $baseDir): Application
    {
        return include $baseDir . '/bootstrap/app.php';
    }

    public function collect(string $baseDir) {
        Collector::getInstance()->collect($baseDir.'/app');
    }

    public function turnOn(string $basePath): Application
    {
        $app = $this->bootEngine($basePath);

        $this->bootstrap($app);

        $this->collect($basePath);

        $this->warmEngine();
        ContextStorage::setCurrentRoutineName('main');
        ContextStorage::setApplication($app);
        return $app;
    }

    private function bootstrap(Application $app): void
    {
        $app->bootstrapWith($this->getBootstrappers($app));

        $app->loadDeferredProviders();
    }

    protected function getBootstrappers(Application $app): array
    {
        $method = (new ReflectionObject(
            $kernel = $app->make(HttpKernelContract::class)
        ))->getMethod('bootstrappers');

        $method->setAccessible(true);

        return $this->injectBootstrapperBefore(
            RegisterProviders::class,
            SetRequestForConsole::class,
            $method->invoke($kernel)
        );
    }

    protected function injectBootstrapperBefore(string $before, string $inject, array $bootstrappers): array
    {
        $injectIndex = array_search($before, $bootstrappers, true);

        if ($injectIndex !== false) {
            array_splice($bootstrappers, $injectIndex, 0, [$inject]);
        }

        return $bootstrappers;
    }

    public function warmEngine()
    {
        $this->wroomWroom();
    }

    private function wroomWroom()
    {
        $collector = Collector::getInstance();
        $collector->runAttributeInstructions(Warmer::class);
    }

    public function run() {
        $collector = Collector::getInstance();
        $collector->runAttributeInstructions(Clutch::class);
    }
}