<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
final readonly class SettingValue
{
    public function __construct(
        public SettingType $type,
        public mixed $value,
    ) {}

    public static function fromRaw(SettingType $type, mixed $value): self
    {
        $normalized = match ($type) {
            SettingType::String => is_string($value) ? $value : (string) $value,
            SettingType::Int => is_int($value) ? $value : (int) $value,
            SettingType::Float => is_float($value) ? $value : (float) $value,
            SettingType::Bool => is_bool($value) ? $value : (bool) $value,
            SettingType::Array => is_array($value) ? $value : (array) $value,
        };

        return new self(type: $type, value: $normalized);
    }

    public function raw(): mixed
    {
        return $this->value;
    }
}
