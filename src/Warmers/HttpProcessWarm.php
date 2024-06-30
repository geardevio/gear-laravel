<?php

namespace GearDev\LaravelBridge\Warmers;

use GearDev\Core\Attributes\Warmer;
use GearDev\Core\Warmers\WarmerInterface;
use GearDev\HttpSwowServer\Container\HttpSwowContainer;
use GearDev\LaravelBridge\HttpServer\HttpServer;

#[Warmer]
class HttpProcessWarm implements WarmerInterface
{

    public function warm(): void
    {
        HttpSwowContainer::setHttpCycleRealization(new HttpServer());
    }
}