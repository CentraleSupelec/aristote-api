<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\CodeQuality\Rector\Class_\InlineClassRoutePrefixRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();

    $rectorConfig->paths([
        __DIR__.'/src',
        //        __DIR__.'/tests',
    ]);

    $rectorConfig->autoloadPaths([
        __DIR__.'/vendor/autoload.php',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    $rectorConfig->sets([
        SetList::DEAD_CODE,
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::NAMING,
        SymfonySetList::SYMFONY_72,
        SymfonySetList::SYMFONY_CODE_QUALITY,
    ]);

    $rectorConfig->skip([
        RenamePropertyToMatchTypeRector::class => [
            __DIR__.'/src/*',
        ],
        RenameParamToMatchTypeRector::class => [
            __DIR__.'/src/*',
        ],
        RenameVariableToMatchMethodCallReturnTypeRector::class => [
            __DIR__.'/src/*',
        ],
        InlineClassRoutePrefixRector::class => [
            __DIR__.'/src/*',
        ],
        NewMethodCallWithoutParenthesesRector::class => [
            __DIR__.'/src/*',
        ],
        NullToStrictStringFuncCallArgRector::class => [
            __DIR__.'/src/Utils',
        ],
    ]);
};
