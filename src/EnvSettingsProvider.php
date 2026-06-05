<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
final readonly class EnvSettingsProvider implements SettingsProvider
{
    private const string ENV_PREFIX = 'APP_SETTING_';

    /**
     * @var array<string, SettingDefinition>
     */
    private array $definitions;

    /**
     * @param array<string, SettingDefinition|array{type: string, default?: mixed}> $definitions
     */
    public function __construct(
        array $definitions = [],
        private string $prefix = self::ENV_PREFIX,
    ) {
        $this->definitions = ConfigSettingsProvider::normalizeDefinitions($definitions);
    }

    #[\Override]
    public function has(string $key): bool
    {
        return isset($this->definitions[$key]) && $this->envValue($key) !== null;
    }

    #[\Override]
    public function get(string $key): mixed
    {
        if (!isset($this->definitions[$key])) {
            throw new Exception\UnknownSettingException(
                message: sprintf('Unknown setting "%s"', $key),
            );
        }

        $envValue = $this->envValue($key);

        if ($envValue === null) {
            throw new Exception\UnknownSettingException(
                message: sprintf('Environment variable for setting "%s" not found', $key),
            );
        }

        return $this->definitions[$key]->cast($envValue);
    }

    private function envValue(string $key): ?string
    {
        $envKey = $this->prefix . strtoupper(string: str_replace(search: '.', replace: '_', subject: $key));

        $value = getenv($envKey);

        if ($value === false) {
            return null;
        }

        return $value;
    }
}
