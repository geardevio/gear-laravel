<?php


/**
 * Load all hacks from the Hacks directory of all frock packages.
 * Sometimes we need to add some hacks to the code to make it work.
 * For example: we need to change Laravel Container with our own implementation.
 *
 * @param string $baseDir
 * @return void
 */
function loadHacks(string $baseDir = __DIR__) {
    $potentialHackDirs = [
        'vendor/geardev/laravel-bridge/hacks/hack.php',
    ];

    foreach ($potentialHackDirs as $hackDir) {
        $hackFile = $baseDir . '/' . $hackDir;
        if (file_exists($hackFile)) {
            require_once $hackFile;
        }
    }
}