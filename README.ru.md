# rasuvaeff/yii3-настройки
[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-settings.svg?label=stable)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-settings.svg)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-settings/actions)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-settings/static-analysis.yml?branch=master&label=psalm)](https://github.com/rasuvaeff/yii3-settings/actions)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-settings/php)](https://packagist.org/packages/rasuvaeff/yii3-settings)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-settings.svg)](LICENSE.md)
Типизированные настройки времени выполнения для Yii3: типизированные геттеры, несколько провайдеров, декоратор кэша, контракт шифрования, инспектор.

 > Используете помощника по программированию с искусственным интеллектом? [llms.txt](llms.txt) содержит компактную ссылку на API
 >, которую вы можете передать LLM, чтобы помочь ей работать с этим пакетом. @@ЛИНИЯ@@
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
### Подключение провайдера (конфигурационный плагин Yii3)
Ядро соединяет только фасад «Настройки». Реализация `SettingsProvider`
 предоставляется **только одним** источником — серверной частью хранилища или, для настроек config-array
, приложением. Это позволяет сохранить доступ к серверным модулям (установите один, и он
 будет подключен автоматически) без конфликта конфигурации «Дублирующийся ключ».

 | Настройка | Как привязан «SettingsProvider» |
 |---|---|
| Database-backed | install [`rasuvaeff/yii3-settings-db`](https://github.com/rasuvaeff/yii3-settings-db) — it binds it automatically |
| Только конфигурация | привяжите его один раз в конфигурации вашего приложения (фрагмент ниже) |

 Для настройки только конфигурации привяжите `SettingsProvider` к `ConfigSettingsProvider` в
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
Свяжите «SettingsProvider» из одного источника — серверная часть плюс ручная привязка
 снова приводит к конфликту «Дублированного ключа». @@ЛИНИЯ@@
### Провайдеры
| Провайдер | Описание |
 |---|---|
 | `ConfigSettingsProvider` | Чтение из массивов конфигурации PHP |
 | `EnvSettingsProvider` | Чтение переменных среды |
 | `ChainSettingsProvider` | Объединяет нескольких поставщиков (победа в первом матче) |
 | `CachedSettingsProvider` | Декоратор кэша PSR-16 (сквозная запись с версии 1.1.0) | @@ЛИНИЯ@@
### Сеть поставщиков
```php
$chain = new ChainSettingsProvider(providers: [
    $envProvider,
    $configProvider,
]);
```
### Декоратор кэша
CachedSettingsProvider — это кэш чтения PSR-16. Начиная с версии 1.1.0 это также
 **сквозная запись**: когда внутренний поставщик реализует `WritableSettingsProvider`,
 `set()`/`remove()` делегирует ему и делает недействительной кэшированную запись для ключа —
, поэтому при чтении никогда не обнаруживается устаревшее значение после записи. Привяжите его как одиночный
 `WritableSettingsProvider`/`SettingsProvider`, и кэш автоматически останется согласованным
. @@ЛИНИЯ@@
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
Неизвестные настройки возвращают значения по умолчанию в нестрогом режиме. Включить строгий режим для выдачи:

```php
$settings = new Settings(
    provider: $provider,
    definitions: $definitions,
    strictMode: true,
);
```
### Тип безопасности
Вызов геттера с неправильным типом вызывает исключение SettingTypeMismatchException:

```php
// Definition: billing.currency = string
$settings->int('billing.currency'); // throws
```
## Публичный API
| Класс | Описание |
 |---|---|
 | `Настройки` | Фасад: `string()`, `int()`, `float()`, `bool()`, `array()`, `has()` |
 | `Определение настройки` | Определение типизированных настроек: ключ, тип, значение по умолчанию, приведение, секретный флаг и дополнительные метаданные представления/политики («метка», «группа», «справка», «выбор», «только для чтения») |
 | `SettingKey` | Подтвержденный объект значения ключа настройки |
 | `SettingValue` | Введенное нормализованное значение настройки |
 | `Тип настройки` | Перечисление: `string`, `int`, `float`, `bool`, `array` |
 | `SettingsProvider` | Интерфейс провайдера только для чтения |
 | `WritableSettingsProvider` | Интерфейс провайдера чтения-записи |
 | `Инспектор настроек` | Модель чтения для администратора: `describe()` возвращает `SettingState` |
 | `SettingState` | Объект-значение: ключ, значение, источник, сохраненное переопределение, секрет, доступный для записи |
 | `ConfigSettingsProvider` | Провайдер из конфиг-массивов |
 | `EnvSettingsProvider` | Поставщик из переменных среды |
 | `ChainSettingsProvider` | Цепочка провайдеров (победа в первом матче) |
 | `CachedSettingsProvider` | Декоратор кэша PSR-16 (сквозная запись с версии 1.1.0) |
 | `Шифр` | Интерфейс шифрования (AEAD со связанными данными) |
 | `DecryptionException` | Ошибка расшифровки (подделанные данные) |
 | `UnknownEncryptionKeyException` | Идентификатор ключа в конверте не найден в KeyRing | @@ЛИНИЯ@@
### Секретные настройки
```php
$def = new SettingDefinition(
    key: 'billing.stripe_key',
    type: SettingType::String,
    secret: true,
);
```
Из конфигурации:

```php
'billing.stripe_key' => ['type' => 'string', 'secret' => true],
```
### Метаданные презентации и политики
Определение может содержать дополнительные подсказки пользовательского интерфейса/политики, используемые инструментами администратора (например,
 `rasuvaeff/yii3-settings-ui`). Они инертны для основных поставщиков, за исключением
 `readonly`, который поставщики, доступные для записи, отвергают, а `describe()` отражает через
`SettingState::isWritable`. @@ЛИНИЯ@@
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
Из конфигурации:

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
| Правило | Деталь |
 |---|---|
 | `секрет=истина` | Разрешено только для `SettingType::String` |
 | `секрет = ложь` | По умолчанию — существующие определения не изменены | @@ЛИНИЯ@@
### Контракт шифрования
```php
use Rasuvaeff\Yii3Settings\Crypto\Cipher;

// Implementations encrypt/decrypt with AAD binding to the setting key
$ciphertext = $cipher->encrypt(plaintext: 'sk_live_xxx', aad: 'billing.stripe_key');
$plaintext = $cipher->decrypt(ciphertext: $ciphertext, aad: 'billing.stripe_key');
```
### Инспектор настроек
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
- Ключи настройки проверяются по `/^[a-z][a-z0-9_.-]*$/`.
 — приведение типов централизовано в `SettingDefinition::cast()`.
 - Несоответствие типов вызывает `SettingTypeMismatchException`; геттеры не возвращают необработанные нетипизированные значения.
 — сбои кэша в `CachedSettingsProvider` рассматриваются как промахи кэша и не обходят проверки типов.
 — Ключи кэша включают пространство имен и версию: `yii3-settings:v1:<key>`.
 — `secret=true` разрешен только для `SettingType::String` — применяется при создании.
 — секретный открытый текст никогда не должен регистрироваться или включаться в сообщения об исключениях (требуется реализацией). @@ЛИНИЯ@@
## Примеры
См. [examples/](examples/) для работоспособных сценариев. @@ЛИНИЯ@@
## Разработка
```bash
make install && make build
```
## Лицензия
BSD-3-пункт. См. [LICENSE.md](LICENSE.md).
