# rasuvaeff/yii3-settings

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-settings.svg?label=stable)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-settings.svg)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-settings/actions)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/static-analysis.yml?branch=master&label=psalm)](https://github.com/rasuvaeff/yii3-settings/actions)
[![Coverage](https://codecov.io/gh/rasuvaeff/yii3-settings/branch/master/graph/badge.svg)](https://codecov.io/gh/rasuvaeff/yii3-settings)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-settings/php)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-settings.svg)](LICENSE.md)

Typed runtime settings for Yii3: typed getters, multiple providers, cache decorator.

> Using an AI coding assistant? [llms.txt](llms.txt) has a compact API reference
> you can give to the LLM to help it work with this package.

## Requirements

- PHP 8.3+
- `psr/simple-cache` ^3.0

## Installation

```bash
composer require rasuvaeff/yii3-settings
```

## Usage

### Typed getters

```php
use Rasuvaeff\Yii3Settings\Settings;

$currency = $settings->string(key: 'billing.currency');
$limit = $settings->int(key: 'orders.max_items');
$enabled = $settings->bool(key: 'mail.enabled');
$features = $settings->array(key: 'app.features');
```

### Setting keys and values

```php
use Rasuvaeff\Yii3Settings\SettingKey;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3Settings\SettingValue;

$key = new SettingKey('billing.currency');
$value = SettingValue::fromRaw(type: SettingType::String, value: 'USD');
```

### Configuration

```php
return [
    'rasuvaeff/yii3-settings' => [
        'definitions' => [
            'billing.currency' => ['type' => 'string', 'default' => 'USD'],
            'orders.max_items' => ['type' => 'int', 'default' => 100],
            'mail.enabled' => ['type' => 'bool', 'default' => true],
        ],
    ],
];
```

### Providers

| Provider | Description |
|---|---|
| `ConfigSettingsProvider` | Reads from PHP config arrays |
| `EnvSettingsProvider` | Reads from environment variables |
| `ChainSettingsProvider` | Chains multiple providers (first match wins) |
| `CachedSettingsProvider` | PSR-16 cache decorator |

### Chain providers

```php
$chain = new ChainSettingsProvider(providers: [
    $envProvider,
    $configProvider,
]);
```

### Cache decorator

```php
$cached = new CachedSettingsProvider(
    inner: $chain,
    cache: $psr16Cache,
    definitions: $definitions,
    ttl: 60,
    cacheNamespace: 'yii3-settings',
    cacheVersion: 1,
);
```

### Strict mode

Unknown settings return type defaults in non-strict mode. Enable strict mode to throw:

```php
$settings = new Settings(
    provider: $provider,
    definitions: $definitions,
    strictMode: true,
);
```

### Type safety

Calling a getter with a wrong type throws `SettingTypeMismatchException`:

```php
// Definition: billing.currency = string
$settings->int('billing.currency'); // throws
```

## Public API

| Class | Description |
|---|---|
| `Settings` | Facade: `string()`, `int()`, `float()`, `bool()`, `array()`, `has()` |
| `SettingDefinition` | Typed setting definition with key, type, default, cast |
| `SettingKey` | Validated setting key value object |
| `SettingValue` | Typed normalized setting value |
| `SettingType` | Enum: `string`, `int`, `float`, `bool`, `array` |
| `SettingsProvider` | Read-only provider interface |
| `WritableSettingsProvider` | Read-write provider interface |
| `ConfigSettingsProvider` | Provider from config arrays |
| `EnvSettingsProvider` | Provider from env variables |
| `ChainSettingsProvider` | Provider chain (first match wins) |
| `CachedSettingsProvider` | PSR-16 cache decorator |

## Security

- Setting keys are validated against `/^[a-z][a-z0-9_.-]*$/`.
- Type coercion is centralized in `SettingDefinition::cast()`.
- Type mismatches throw `SettingTypeMismatchException`; getters do not return raw untyped values.
- Cache failures in `CachedSettingsProvider` are treated as cache misses and do not bypass type checks.
- Cache keys include namespace and version: `yii3-settings:v1:<key>`.

## Examples

See [examples/](examples/) for runnable scripts.
Examples are expected to execute without fatal errors and stay aligned with the
documented public API.

## Development

```bash
make install
make build
make cs-fix
make test
make test-coverage
make mutation
make release-check
```

`make test-coverage` and `make mutation` bootstrap `pcov` inside the
`composer:2` container because the base image has no coverage driver.

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
