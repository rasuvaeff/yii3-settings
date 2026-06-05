<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\Settings;
use Rasuvaeff\Yii3Settings\SettingsProvider;

/** @var array $params */

return [
    SettingsProvider::class => [
        'class' => ConfigSettingsProvider::class,
        '__construct()' => [
            'definitions' => $params['rasuvaeff/yii3-settings']['definitions'],
            'values' => $params['rasuvaeff/yii3-settings']['values'],
        ],
    ],
    Settings::class => [
        'definition' => static function () use ($params): Settings {
            $provider = new ConfigSettingsProvider(
                definitions: $params['rasuvaeff/yii3-settings']['definitions'],
                values: $params['rasuvaeff/yii3-settings']['values'],
            );

            return new Settings(
                provider: $provider,
                definitions: $provider->getDefinitions(),
                strictMode: $params['rasuvaeff/yii3-settings']['strictMode'],
            );
        },
    ],
];
