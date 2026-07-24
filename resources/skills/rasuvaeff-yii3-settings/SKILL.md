---
name: rasuvaeff-yii3-settings
description: >-
  Typed runtime settings for Yii3 with rasuvaeff/yii3-settings — Settings
  facade (string()/int()/float()/bool()/array()), SettingDefinition,
  SettingType, ConfigSettingsProvider, EnvSettingsProvider,
  ChainSettingsProvider, CachedSettingsProvider. Use when writing, reviewing
  or debugging application settings, their DI wiring, providers, caching or
  secret handling in a project that has this package installed.
---

# rasuvaeff/yii3-settings

Typed runtime settings: definitions with types and defaults, typed getters on
a `Settings` facade, swappable providers (config, env, chain, PSR-16 cache
decorator). Namespace `Rasuvaeff\Yii3Settings\`.

## Safety rules — verify these on every change

1. **Never bind `SettingsProvider` in this package's DI config.** The core
   `config/di.php` binds ONLY the `Settings` facade. The `SettingsProvider`
   interface is bound by exactly one source: a storage backend
   (`yii3-settings-db`) or the application (config-only setups). Two sources
   binding it in the `di` group crash `yiisoft/config` with `Duplicate key`.

2. **Typed getters throw on type mismatch.** `string()` on an `Int` setting
   throws `SettingTypeMismatchException` — match the getter to the
   definition's `SettingType`. Unknown key: type default in non-strict mode,
   `UnknownSettingException` in strict mode (never `null`).

3. **`secret: true` is only valid for `SettingType::String`** — enforced in
   the `SettingDefinition` constructor; any other type throws.

4. **Chain provider: first match wins.** Provider order in
   `ChainSettingsProvider` is significant — put overrides (env) before
   fallbacks (config).

5. **Cache keys are dot-separated** (`yii3-settings.v1.<key>`), because PSR-16
   reserves `{}()/\@:` in keys. Never build a colon-separated cache key.

## Canonical usage

```php
use Rasuvaeff\Yii3Settings\{Settings, ConfigSettingsProvider, SettingDefinition, SettingType};

$provider = new ConfigSettingsProvider(
    definitions: [
        'mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String, default: 'noreply@example.com'),
        'orders.max_items' => new SettingDefinition(key: 'orders.max_items', type: SettingType::Int, default: 100),
    ],
    values: ['mail.from' => 'admin@example.com'],
);

$settings = new Settings(provider: $provider, definitions: $provider->getDefinitions());

$settings->string(key: 'mail.from');     // 'admin@example.com'
$settings->int(key: 'orders.max_items'); // 100 (default, no value set)
```

Setting keys must match `/^[a-z][a-z0-9_.-]*$/`. The env provider maps
`mail.enabled` → `APP_SETTING_MAIL_ENABLED` (prefix + upper + dots→underscores).

## Full API

The complete reference — every provider, `SettingsInspector`/`SettingState`,
the `Cipher` contract and all exceptions — ships with the package: read
`vendor/rasuvaeff/yii3-settings/llms.txt` before guessing a method name.
