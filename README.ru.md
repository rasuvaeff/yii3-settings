# rasuvaeff/yii3-settings

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-settings.svg?label=stable)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-settings.svg)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-settings/actions)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/static-analysis.yml?branch=master&label=psalm)](https://github.com/rasuvaeff/yii3-settings/actions)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-settings/php)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-settings.svg)](LICENSE.md)
[English version](README.md)

Типизированные runtime-настройки для Yii3: типизированные геттеры, несколько
провайдеров, cache-декоратор, контракт шифрования, инспектор.

> Используете AI-ассистента? В [llms.txt](llms.txt) — компактный API-справочник,
> который можно передать LLM, чтобы помочь работать с пакетом.
> Проекты с Composer-плагином [llm/skills](https://github.com/roxblnfk/skills)
> дополнительно получают agent-скилл этого пакета в `.agents/skills/`
> автоматически при установке.

## Требования

- PHP 8.3+
- `psr/simple-cache` ^3.0

## Установка

```bash
composer require rasuvaeff/yii3-settings
```

## Использование

### Типизированные геттеры

```php
use Rasuvaeff\Yii3Settings\Settings;

$currency = $settings->string(key: 'billing.currency');
$limit = $settings->int(key: 'orders.max_items');
$enabled = $settings->bool(key: 'mail.enabled');
$features = $settings->array(key: 'app.features');
```

### Установка ключей и значений

```php
use Rasuvaeff\Yii3Settings\SettingKey;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3Settings\SettingValue;

$key = new SettingKey('billing.currency');
$value = SettingValue::fromRaw(type: SettingType::String, value: 'USD');
```

### Конфигурация

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

### Подключение провайдера (config-plugin Yii3)

Ядро биндит только фасад `Settings`. Реализацию `SettingsProvider` предоставляет
**ровно один** источник — storage-бэкенд или, для config-array-настроек,
приложение. Это позволяет backend-ам быть drop-in (поставил один — и он уже
привязан) без конфликта `Duplicate key` в конфиге.

| Сценарий | Как биндится `SettingsProvider` |
|---|---|
| Database-backed | установите [`rasuvaeff/yii3-settings-db`](https://github.com/rasuvaeff/yii3-settings-db) — он биндит автоматически |
| Config-only | забиндите один раз в конфиге приложения (сниппет ниже) |

Для config-only-сценария забиндите `SettingsProvider` в `ConfigSettingsProvider`
в `config/common/di/*.php`:

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

Биндите `SettingsProvider` из одного источника — backend плюс ручная привязка
вновь приведут к конфликту `Duplicate key`.

### Провайдеры

| Провайдер | Описание |
|---|---|
| `ConfigSettingsProvider` | Чтение из PHP config-массивов |
| `EnvSettingsProvider` | Чтение из переменных окружения |
| `ChainSettingsProvider` | Цепочка провайдеров (выигрывает первое совпадение) |
| `CachedSettingsProvider` | PSR-16 cache-декоратор (write-through с 1.1.0) |

### Chain-провайдеры

```php
$chain = new ChainSettingsProvider(providers: [
    $envProvider,
    $configProvider,
]);
```

### Cache-декоратор

`CachedSettingsProvider` — это PSR-16 read-кэш. С 1.1.0 он также
**write-through**: когда внутренний провайдер реализует
`WritableSettingsProvider`, `set()`/`remove()` делегируют ему и инвалидируют
закэшированную запись для ключа — поэтому чтение никогда не возвращает устаревшее
значение после записи. Забиндите его как единственный
`WritableSettingsProvider`/`SettingsProvider`, и кэш остаётся когерентным
автоматически.

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

### Строгий режим

Неизвестные настройки возвращают типовой default в нестрогом режиме. Включите
строгий режим, чтобы бросать исключение:

```php
$settings = new Settings(
    provider: $provider,
    definitions: $definitions,
    strictMode: true,
);
```

### Типобезопасность

Вызов геттера с неверным типом бросает `SettingTypeMismatchException`:

```php
// Definition: billing.currency = string
$settings->int('billing.currency'); // throws
```

## Публичный API

| Класс | Описание |
|---|---|
| `Settings` | Фасад: `string()`, `int()`, `float()`, `bool()`, `array()`, `has()` |
| `SettingDefinition` | Типизированное определение настройки: key, type, default, cast, флаг secret и опциональные presentation/policy-метаданные (`label`, `group`, `help`, `choices`, `readonly`) |
| `SettingKey` | Валидируемый value object ключа настройки |
| `SettingValue` | Типизированное нормализованное значение настройки |
| `SettingType` | Enum: `string`, `int`, `float`, `bool`, `array` |
| `SettingsProvider` | Интерфейс read-only провайдера |
| `WritableSettingsProvider` | Интерфейс read-write провайдера |
| `SettingsInspector` | Admin-facing read-model: `describe()` возвращает `SettingState` |
| `SettingState` | Value object: key, value, source, хранимый override, secret, writable |
| `ConfigSettingsProvider` | Провайдер из config-массивов |
| `EnvSettingsProvider` | Провайдер из переменных окружения |
| `ChainSettingsProvider` | Цепочка провайдеров (выигрывает первое совпадение) |
| `CachedSettingsProvider` | PSR-16 cache-декоратор (write-through с 1.1.0) |
| `Cipher` | Интерфейс шифрования (AEAD with associated data) |
| `DecryptionException` | Ошибка расшифровки (tampered data) |
| `UnknownEncryptionKeyException` | ID ключа в envelope не найден в KeyRing |

### Секретные настройки

```php
$def = new SettingDefinition(
    key: 'billing.stripe_key',
    type: SettingType::String,
    secret: true,
);
```

Из конфига:

```php
'billing.stripe_key' => ['type' => 'string', 'secret' => true],
```

### Presentation- и policy-метаданные

Определение может нести опциональные UI/policy-хинты для admin-инструментария
(например, `rasuvaeff/yii3-settings-ui`). Для core-провайдеров они инертны, за
исключением `readonly` — writable-провайдеры его отклоняют, а `describe()`
отражает это через `SettingState::isWritable`.

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

Из конфига:

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

| Правило | Детали |
|---|---|
| `secret=true` | Разрешено только для `SettingType::String` |
| `secret=false` | По умолчанию — существующие определения не меняются |

### Контракт шифрования

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

## Безопасность

- Ключи настроек валидируются по `/^[a-z][a-z0-9_.-]*$/`.
- Приведение типов централизовано в `SettingDefinition::cast()`.
- Несовпадение типа бросает `SettingTypeMismatchException`; геттеры не
  возвращают сырые нетипизированные значения.
- Сбои кэша в `CachedSettingsProvider` считаются cache miss и не обходят
  проверки типов.
- Cache-ключи включают namespace и версию: `yii3-settings:v1:<key>`.
- `secret=true` разрешён только для `SettingType::String` — инфорсится в
  конструкторе.
- Секретный открытый текст никогда не должен логироваться или попадать в
  сообщения исключений (инфорсится реализациями).

## Примеры

См. [examples/](examples/) — запускаемые скрипты.

## Разработка

```bash
make install && make build
```

## Лицензия

BSD-3-Clause. См. [LICENSE.md](LICENSE.md).
