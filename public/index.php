<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Register The Auto Loader
if (file_exists($autoload = __DIR__.'/../vendor/autoload.php')) {
    require $autoload;
}

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';

// Handle The Request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
