<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
final readonly class SettingDefinition
{
    public string $key;

    public SettingType $type;

    public mixed $default;

    public SettingKey $settingKey;

    public ?SettingValue $defaultValue;

    public function __construct(
        SettingKey|string $key,
        SettingType $type,
        mixed $default = null,
    ) {
        $this->settingKey = $key instanceof SettingKey ? $key : new SettingKey($key);
        $this->key = $this->settingKey->toString();
        $this->type = $type;
        $this->defaultValue = $default === null ? null : SettingValue::fromRaw(type: $type, value: $default);
        $this->default = $this->defaultValue?->raw();
    }

    /**
     * @param array{type: string, default?: mixed} $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            type: SettingType::from($config['type']),
            default: $config['default'] ?? null,
        );
    }

    public function cast(mixed $value): mixed
    {
        return SettingValue::fromRaw(type: $this->type, value: $value)->raw();
    }

    public function hasDefault(): bool
    {
        return $this->defaultValue !== null;
    }
}
