<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
final readonly class ConfigSettingsProvider implements SettingsProvider
{
    /**
     * @var array<string, SettingDefinition>
     */
    private array $definitions;

    /**
     * @param array<string, SettingDefinition|array{type: string, default?: mixed}> $definitions
     * @param array<string, mixed> $values
     */
    public function __construct(
        array $definitions = [],
        private array $values = [],
    ) {
        $this->definitions = self::normalizeDefinitions($definitions);
    }

    #[\Override]
    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    #[\Override]
    public function get(string $key): mixed
    {
        if (!isset($this->definitions[$key])) {
            throw new Exception\UnknownSettingException(
                message: sprintf('Unknown setting "%s"', $key),
            );
        }

        $definition = $this->definitions[$key];

        if (array_key_exists(key: $key, array: $this->values)) {
            return $definition->cast($this->values[$key]);
        }

        return $definition->default;
    }

    /**
     * @return array<string, SettingDefinition>
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @param array<string, SettingDefinition|array{type: string, default?: mixed, secret?: bool}> $definitions
     *
     * @return array<string, SettingDefinition>
     */
    public static function normalizeDefinitions(array $definitions): array
    {
        $result = [];

        foreach ($definitions as $key => $definition) {
            $result[$key] = $definition instanceof SettingDefinition
                ? $definition
                : SettingDefinition::fromConfig(key: $key, config: $definition);
        }

        return $result;
    }
}
