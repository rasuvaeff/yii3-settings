# rasuvaeff/yii3-settings

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-settings.svg?label=stable)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-settings.svg)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-settings/actions)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/static-analysis.yml?branch=master&label=psalm)](https://github.com/rasuvaeff/yii3-settings/actions)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-settings/php)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-settings.svg)](LICENSE.md)

Typed runtime settings for Yii3: typed getters, multiple providers, cache decorator, encryption contract, inspector.

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

### Provider wiring (Yii3 config-plugin)

The core wires only the `Settings` facade. The `SettingsProvider` implementation
is supplied by **exactly one** source — a storage backend or, for config-array
settings, the application. This keeps backends drop-in (install one and it is
wired automatically) with no `Duplicate key` config conflict.

| Setup | How `SettingsProvider` is bound |
|---|---|
| Database-backed | install [`rasuvaeff/yii3-settings-db`](https://github.com/rasuvaeff/yii3-settings-db) — it binds it automatically |
| Config-only | bind it once in your app config (snippet below) |

For a config-only setup, bind `SettingsProvider` to `ConfigSettingsProvider` in
`config/common/di/*.php`:

```php
use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
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
];
```

Bind `SettingsProvider` from a single source — a backend plus a manual binding
reintroduces the `Duplicate key` conflict.

### Providers

| Provider | Description |
|---|---|
| `ConfigSettingsProvider` | Reads from PHP config arrays |
| `EnvSettingsProvider` | Reads from environment variables |
| `ChainSettingsProvider` | Chains multiple providers (first match wins) |
| `CachedSettingsProvider` | PSR-16 cache decorator (write-through since 1.1.0) |

### Chain providers

```php
$chain = new ChainSettingsProvider(providers: [
    $envProvider,
    $configProvider,
]);
```

### Cache decorator

`CachedSettingsProvider` is a PSR-16 read cache. Since 1.1.0 it is also
**write-through**: when the inner provider implements `WritableSettingsProvider`,
`set()`/`remove()` delegate to it and invalidate the cached entry for the key —
so reads never observe a stale value after a write. Bind it as the single
`WritableSettingsProvider`/`SettingsProvider` and the cache stays coherent
automatically.

```php
$cached = new CachedSettingsProvider(
    inner: $writableProvider, // write-through: writes delegate + clear the cache key
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
| `SettingDefinition` | Typed setting definition: key, type, default, cast, secret flag, and optional presentation/policy metadata (`label`, `group`, `help`, `choices`, `readonly`) |
| `SettingKey` | Validated setting key value object |
| `SettingValue` | Typed normalized setting value |
| `SettingType` | Enum: `string`, `int`, `float`, `bool`, `array` |
| `SettingsProvider` | Read-only provider interface |
| `WritableSettingsProvider` | Read-write provider interface |
| `SettingsInspector` | Admin-facing read-model: `describe()` returns `SettingState` |
| `SettingState` | Value object: key, value, source, stored override, secret, writable |
| `ConfigSettingsProvider` | Provider from config arrays |
| `EnvSettingsProvider` | Provider from environment variables |
| `ChainSettingsProvider` | Provider chain (first match wins) |
| `CachedSettingsProvider` | PSR-16 cache decorator (write-through since 1.1.0) |
| `Cipher` | Encryption interface (AEAD with associated data) |
| `DecryptionException` | Decryption failure (tampered data) |
| `UnknownEncryptionKeyException` | Key ID in envelope not found in KeyRing |

### Secret settings

```php
$def = new SettingDefinition(
    key: 'billing.stripe_key',
    type: SettingType::String,
    secret: true,
);
```

From config:

```php
'billing.stripe_key' => ['type' => 'string', 'secret' => true],
```

### Presentation & policy metadata

A definition can carry optional UI/policy hints used by admin tooling (e.g.
`rasuvaeff/yii3-settings-ui`). They are inert for the core providers, except
`readonly`, which writable providers reject and `describe()` reflects via
`SettingState::isWritable`.

```php
$def = new SettingDefinition(
    key: 'orders.status',
    type: SettingType::String,
    default: 'new',
    label: 'Default order status',
    group: 'Orders',
    help: 'Status assigned to freshly created orders',
    choices: ['new', 'paid', 'shipped'],
    readonly: false,
);
```

From config:

```php
'orders.status' => [
    'type' => 'string',
    'default' => 'new',
    'label' => 'Default order status',
    'group' => 'Orders',
    'help' => 'Status assigned to freshly created orders',
    'choices' => ['new', 'paid', 'shipped'],
    'readonly' => false,
],
```

| Rule | Detail |
|---|---|
| `secret=true` | Only allowed for `SettingType::String` |
| `secret=false` | Default — existing definitions unchanged |

### Encryption contract

```php
use Rasuvaeff\Yii3Settings\Crypto\Cipher;

// Implementations encrypt/decrypt with AAD binding to the setting key
$ciphertext = $cipher->encrypt(plaintext: 'sk_live_xxx', aad: 'billing.stripe_key');
$plaintext = $cipher->decrypt(ciphertext: $ciphertext, aad: 'billing.stripe_key');
```

### SettingsInspector

```php
use Rasuvaeff\Yii3Settings\SettingsInspector;

$state = $inspector->describe(key: 'billing.currency');
$state->key;               // 'billing.currency'
$state->effectiveValue;    // 'USD' (or null for masked secrets)
$state->hasStoredOverride; // true
$state->source;            // 'db', 'config', or 'default'
$state->isSecret;          // false
$state->isWritable;        // true (false for readonly definitions)

$states = $inspector->describeAll(); // list<SettingState> for every declared key
```

## Security

- Setting keys are validated against `/^[a-z][a-z0-9_.-]*$/`.
- Type coercion is centralized in `SettingDefinition::cast()`.
- Type mismatches throw `SettingTypeMismatchException`; getters do not return raw untyped values.
- Cache failures in `CachedSettingsProvider` are treated as cache misses and do not bypass type checks.
- Cache keys include namespace and version: `yii3-settings:v1:<key>`.
- `secret=true` is only allowed for `SettingType::String` — enforced at construction.
- Secret plaintext must never be logged or included in exception messages (enforced by implementations).

## Examples

See [examples/](examples/) for runnable scripts.

## Development

```bash
make install && make build
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
