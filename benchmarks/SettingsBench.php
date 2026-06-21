<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Benchmarks;

use Rasuvaeff\Yii3Settings\ChainSettingsProvider;
use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Testo\Bench;

final class SettingsBench
{
    #[Bench(
        callables: [
            'chain' => [self::class, 'getViaChain'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function getViaConfig(): mixed
    {
        $provider = new ConfigSettingsProvider(
            definitions: [
                'app.name' => new SettingDefinition(key: 'app.name', type: SettingType::String, default: 'MyApp'),
                'app.debug' => new SettingDefinition(key: 'app.debug', type: SettingType::Bool, default: false),
                'app.timeout' => new SettingDefinition(key: 'app.timeout', type: SettingType::Int, default: 30),
            ],
            values: ['app.name' => 'Production', 'app.debug' => '0', 'app.timeout' => '60'],
        );

        return $provider->get('app.name');
    }

    public static function getViaChain(): mixed
    {
        $primary = new ConfigSettingsProvider(
            definitions: [
                'app.name' => new SettingDefinition(key: 'app.name', type: SettingType::String, default: 'MyApp'),
                'app.debug' => new SettingDefinition(key: 'app.debug', type: SettingType::Bool, default: false),
            ],
            values: ['app.name' => 'Production'],
        );
        $fallback = new ConfigSettingsProvider(
            definitions: [
                'app.timeout' => new SettingDefinition(key: 'app.timeout', type: SettingType::Int, default: 30),
            ],
        );
        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        return $chain->get('app.name');
    }
}
