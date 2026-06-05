<?php

declare(strict_types=1);

/**
 * Yii3 config-plugin integration example.
 *
 * In a real Yii3 application, config/params.php and config/di.php are merged
 * automatically by yiisoft/config and the Settings service is resolved from
 * the DI container. This script shows the same wiring manually.
 */

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\Settings;

// Application config (typically in config/params.php)
$appParams = [
    'rasuvaeff/yii3-settings' => [
        'definitions' => [
            'billing.currency' => ['type' => 'string', 'default' => 'USD'],
            'orders.max_items' => ['type' => 'int', 'default' => 100],
            'mail.enabled' => ['type' => 'bool', 'default' => true],
        ],
        'values' => [
            'billing.currency' => 'EUR',
        ],
        'strictMode' => true,
    ],
];

// DI wiring (typically in config/di.php)
$config = $appParams['rasuvaeff/yii3-settings'];

$provider = new ConfigSettingsProvider(
    definitions: $config['definitions'],
    values: $config['values'],
);

$settings = new Settings(
    provider: $provider,
    definitions: $provider->getDefinitions(),
    strictMode: $config['strictMode'],
);

// Usage in application code
echo 'billing.currency: ' . $settings->string(key: 'billing.currency') . "\n";
echo 'orders.max_items: ' . $settings->int(key: 'orders.max_items') . "\n";
echo 'mail.enabled: ' . ($settings->bool(key: 'mail.enabled') ? 'true' : 'false') . "\n";

// Strict mode: unknown key throws instead of returning a type default
try {
    $settings->string(key: 'unknown.key');
} catch (\Rasuvaeff\Yii3Settings\Exception\UnknownSettingException $e) {
    echo 'strict mode rejected unknown key: ' . $e->getMessage() . "\n";
}
