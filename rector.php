<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        namedArgs: true,
        earlyReturn: true,
        carbon: true,
    )
    ->withSets([
        LaravelSetList::LARAVEL_120,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
    ])
    ->withTypeCoverageLevel(0)
    ->withSkip([
        AppToResolveRector::class,
    ]);
