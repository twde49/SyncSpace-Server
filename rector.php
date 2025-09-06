<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    // Règles de base pour corriger les erreurs PHPStan communes
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SymfonySetList::SYMFONY_70,
    ]);

    $rectorConfig->skip([
        __DIR__.'/var',
        __DIR__.'/vendor',
    ]);
};
