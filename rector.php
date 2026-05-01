<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\ValueObject\PhpVersion;
use RectorLaravel\Rector\Class_\AddExtendsAnnotationToModelFactoriesRector;
use RectorLaravel\Set\LaravelLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        AddExtendsAnnotationToModelFactoriesRector::class,
        DisallowedEmptyRuleFixerRector::class,
        ExplicitBoolCompareRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
    ]);

    $rectorConfig->importNames();

    $rectorConfig->sets([
        LaravelLevelSetList::UP_TO_LARAVEL_120,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        PHPUnitSetList::PHPUNIT_120,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_85);
};
