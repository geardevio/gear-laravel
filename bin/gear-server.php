<?php

use GearDev\LaravelBridge\Starter\Ignition;

const IS_GEAR_SERVER = true;

define('LARAVEL_START', microtime(true));

$hackPath = dirname($GLOBALS['_composer_autoload_path']).'/geardev/laravel-bridge/helpers/hack-load-helpers.php';
require_once $hackPath;

loadHacks(dirname($GLOBALS['_composer_autoload_path']).'/../');

file_put_contents(
    dirname($GLOBALS['_composer_autoload_path']).'/../artisan',
    '#!/usr/bin/env php' . PHP_EOL . '<?php' . PHP_EOL . 'require_once __DIR__ . \'/vendor/bin/gear.php\';' . PHP_EOL);
require_once $GLOBALS['_composer_autoload_path'];

$ignition = new Ignition();
$ignition->collect(realpath(dirname($GLOBALS['_composer_autoload_path']).'/../'));
$ignition->warmEngine();
$ignition->run();
echo 'Server started'."\n";

while (true) {
    sleep(10);
}
