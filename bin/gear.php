<?php

use GearDev\LaravelBridge\Starter\Ignition;
use Symfony\Component\Console\Input\ArgvInput;

const IS_GEAR_SERVER = false;

define('LARAVEL_START', microtime(true));

require_once __DIR__ . '/../helpers/hack-load-helpers.php';
loadHacks(dirname($GLOBALS['_composer_autoload_path']).'/../');

file_put_contents(
    dirname($GLOBALS['_composer_autoload_path']).'/../artisan',
    '#!/usr/bin/env php' . PHP_EOL . '<?php' . PHP_EOL . 'require_once __DIR__ . \'/vendor/bin/gear.php\';' . PHP_EOL);
require_once $GLOBALS['_composer_autoload_path'];

$ignition = new Ignition();
$laravelApp = $ignition->turnOn(realpath(dirname($GLOBALS['_composer_autoload_path']).'/../'));
$ignition->run();

$status = $laravelApp
    ->handleCommand(new ArgvInput);
/*
|--------------------------------------------------------------------------
| Shutdown The Application
|--------------------------------------------------------------------------
|
| Once Artisan has finished running, we will fire off the shutdown events
| so that any final work may be done by the application before we shut
| down the process. This is the last thing to happen to the request.
|
*/
exit($status);
