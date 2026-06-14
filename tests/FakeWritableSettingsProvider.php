<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\WritableSettingsProvider;

/**
 * @internal
 */
final class FakeWritableSettingsProvider implements WritableSettingsProvider
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values = [],
    ) {}

    #[\Override]
    public function has(string $key): bool
    {
        return array_key_exists(key: $key, array: $this->values);
    }

    #[\Override]
    public function get(string $key): mixed
    {
        if (!array_key_exists(key: $key, array: $this->values)) {
            throw new UnknownSettingException(
                message: sprintf('Unknown setting "%s"', $key),
            );
        }

        return $this->values[$key];
    }

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    #[\Override]
    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->values;
    }
}
