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

    /**
     * @param list<string>|null $choices Allowed values for presentation/selection (optional).
     */
    public function __construct(
        SettingKey|string $key,
        SettingType $type,
        mixed $default = null,
        public bool $secret = false,
        public ?string $label = null,
        public ?string $group = null,
        public ?string $help = null,
        public ?array $choices = null,
        public bool $readonly = false,
    ) {
        if ($secret && $type !== SettingType::String) {
            throw new \InvalidArgumentException('Secret flag is only supported for string type settings');
        }

        $this->settingKey = $key instanceof SettingKey ? $key : new SettingKey($key);
        $this->key = $this->settingKey->toString();
        $this->type = $type;
        $this->defaultValue = $default === null ? null : SettingValue::fromRaw(type: $type, value: $default);
        $this->default = $this->defaultValue?->raw();
    }

    /**
     * @param array{type: string, default?: mixed, secret?: bool, label?: string, group?: string, help?: string, choices?: list<string>, readonly?: bool} $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            type: SettingType::from($config['type']),
            default: $config['default'] ?? null,
            secret: $config['secret'] ?? false,
            label: $config['label'] ?? null,
            group: $config['group'] ?? null,
            help: $config['help'] ?? null,
            choices: $config['choices'] ?? null,
            readonly: $config['readonly'] ?? false,
        );
    }

    public function cast(mixed $value): mixed
    {
        return SettingValue::fromRaw(type: $this->type, value: $value)->raw();
    }

    public function hasDefault(): bool
    {
        return $this->defaultValue instanceof \Rasuvaeff\Yii3Settings\SettingValue;
    }

    public function isSecret(): bool
    {
        return $this->secret;
    }
}
