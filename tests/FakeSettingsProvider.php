<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingsProvider;

/**
 * @internal
 */
final class FakeSettingsProvider implements SettingsProvider
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private readonly array $values = [],
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
}
