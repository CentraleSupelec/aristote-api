<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();

    $rectorConfig->paths([
        __DIR__.'/src',
//        __DIR__.'/tests',
    ]);

    $rectorConfig->autoloadPaths([
        __DIR__.'/vendor/autoload.php',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_82);

    $rectorConfig->sets([
        SetList::DEAD_CODE,
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::NAMING,
        SymfonySetList::SYMFONY_62,
        SymfonySetList::SYMFONY_CODE_QUALITY,
    ]);

    $rectorConfig->skip([
        RenamePropertyToMatchTypeRector::class => [
            __DIR__.'/src/Entity/*',
            __DIR__.'/src/Model/*',
        ],
        RenameParamToMatchTypeRector::class => [
            __DIR__.'/src/Admin/*',
            __DIR__.'/src/Model/*',
        ],
        RenameVariableToMatchMethodCallReturnTypeRector::class => [
            __DIR__.'/src/Service/*',
            __DIR__.'/src/Controller/Api/*',
        ],
    ]);
};
