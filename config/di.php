<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\Settings;
use Rasuvaeff\Yii3Settings\SettingsProvider;

/** @var array $params */

return [
    Settings::class => static fn (SettingsProvider $provider): Settings => new Settings(
        provider: $provider,
        definitions: ConfigSettingsProvider::normalizeDefinitions(
            $params['rasuvaeff/yii3-settings']['definitions'],
        ),
        strictMode: $params['rasuvaeff/yii3-settings']['strictMode'],
    ),
];
