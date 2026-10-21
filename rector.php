<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withCache(__DIR__.'/build/rector')
    ->withSkip([
        // Strips the generic self<TItem>|null docblocks PHPStan needs.
        RemoveUselessUnionReturnDocblockRector::class => [__DIR__.'/src/Support/CursorPage.php'],
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
