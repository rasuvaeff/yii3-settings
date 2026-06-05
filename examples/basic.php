<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\Settings;
use Rasuvaeff\Yii3Settings\SettingType;

$definitions = [
    'mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String, default: 'noreply@example.com'),
    'orders.max_items' => new SettingDefinition(key: 'orders.max_items', type: SettingType::Int, default: 100),
    'mail.enabled' => new SettingDefinition(key: 'mail.enabled', type: SettingType::Bool, default: true),
    'billing.rate' => new SettingDefinition(key: 'billing.rate', type: SettingType::Float, default: 0.0),
];

$provider = new ConfigSettingsProvider(
    definitions: $definitions,
    values: [
        'mail.from' => 'admin@example.com',
        'orders.max_items' => 50,
    ],
);

$settings = new Settings(provider: $provider, definitions: $provider->getDefinitions());

echo "mail.from: " . $settings->string(key: 'mail.from') . "\n";
echo "orders.max_items: " . $settings->int(key: 'orders.max_items') . "\n";
echo "mail.enabled: " . ($settings->bool(key: 'mail.enabled') ? 'true' : 'false') . "\n";
echo "billing.rate: " . $settings->float(key: 'billing.rate') . "\n";
echo "unknown.string (default): '" . $settings->string(key: 'unknown') . "'\n";
