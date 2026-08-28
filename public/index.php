<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
define('TESTBENCH_WORKING_PATH', dirname(__DIR__));

// Bootstrap the testbench-core Laravel skeleton autoloader,
// which reads TESTBENCH_WORKING_PATH and loads vendor/autoload.php.
require dirname(__DIR__).'/vendor/orchestra/testbench-core/laravel/bootstrap/autoload.php';

/** @var Application $app */
$app = require_once dirname(__DIR__).'/vendor/orchestra/testbench-core/laravel/bootstrap/app.php';

$app->handleRequest(Request::capture());
