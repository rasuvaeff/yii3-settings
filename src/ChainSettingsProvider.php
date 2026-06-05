<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
final readonly class ChainSettingsProvider implements SettingsProvider
{
    /**
     * @param list<SettingsProvider> $providers
     */
    public function __construct(
        private array $providers = [],
    ) {}

    #[\Override]
    public function has(string $key): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($key)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function get(string $key): mixed
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($key)) {
                return $provider->get($key);
            }
        }

        throw new Exception\UnknownSettingException(
            message: sprintf('Unknown setting "%s"', $key),
        );
    }
}
