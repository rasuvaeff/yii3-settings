<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
final readonly class Settings
{
    /**
     * @param array<string, SettingDefinition> $definitions
     */
    public function __construct(
        private SettingsProvider $provider,
        private array            $definitions = [],
        private bool             $strictMode = false,
    ) {}

    public function string(SettingKey|string $key): string
    {
        $key = $this->normalizeKey($key);
        $this->assertType(key: $key, expected: SettingType::String);

        return (string) $this->resolve(key: $key, default: '');
    }

    public function int(SettingKey|string $key): int
    {
        $key = $this->normalizeKey($key);
        $this->assertType(key: $key, expected: SettingType::Int);

        return (int) $this->resolve(key: $key, default: 0);
    }

    public function float(SettingKey|string $key): float
    {
        $key = $this->normalizeKey($key);
        $this->assertType(key: $key, expected: SettingType::Float);

        return (float) $this->resolve(key: $key, default: 0.0);
    }

    public function bool(SettingKey|string $key): bool
    {
        $key = $this->normalizeKey($key);
        $this->assertType(key: $key, expected: SettingType::Bool);

        return (bool) $this->resolve(key: $key, default: false);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function array(SettingKey|string $key): array
    {
        $key = $this->normalizeKey($key);
        $this->assertType(key: $key, expected: SettingType::Array);

        return (array) $this->resolve(key: $key, default: []);
    }

    public function has(SettingKey|string $key): bool
    {
        return $this->provider->has($this->normalizeKey($key));
    }

    private function resolve(string $key, mixed $default): mixed
    {
        if (!$this->provider->has($key)) {
            if ($this->strictMode) {
                throw new Exception\UnknownSettingException(
                    message: sprintf('Unknown setting "%s"', $key),
                );
            }

            if (isset($this->definitions[$key]) && $this->definitions[$key]->default !== null) {
                return $this->definitions[$key]->default;
            }

            return $default;
        }

        return $this->provider->get($key);
    }

    private function assertType(string $key, SettingType $expected): void
    {
        if (!isset($this->definitions[$key])) {
            return;
        }

        if ($this->definitions[$key]->type !== $expected) {
            throw new Exception\SettingTypeMismatchException(
                message: sprintf(
                    'Setting "%s" is defined as %s, requested %s',
                    $key,
                    $this->definitions[$key]->type->value,
                    $expected->value,
                ),
            );
        }
    }

    private function normalizeKey(SettingKey|string $key): string
    {
        return $key instanceof SettingKey ? $key->toString() : $key;
    }
}
