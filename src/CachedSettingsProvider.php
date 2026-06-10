<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @api
 */
final readonly class CachedSettingsProvider implements SettingsProvider
{
    private int $ttl;

    /**
     * @var array<string, SettingDefinition>
     */
    private array $definitions;

    /**
     * @param array<string, SettingDefinition|array{type: string, default?: mixed}> $definitions
     */
    public function __construct(
        private SettingsProvider $inner,
        private CacheInterface $cache,
        array $definitions = [],
        int $ttl = 60,
        private string $cacheNamespace = 'yii3-settings',
        private int $cacheVersion = 1,
    ) {
        $this->definitions = ConfigSettingsProvider::normalizeDefinitions($definitions);
        $this->ttl = $ttl;
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

        $cacheKey = $this->cacheKey($key);

        try {
            $cached = $this->cache->get(key: $cacheKey);

            if ($cached !== null) {
                return $this->definitions[$key]->cast($cached);
            }
        } catch (InvalidArgumentException) {
            // Fall through to inner provider.
        }

        /** @var mixed $value */
        $value = $this->inner->get($key);

        try {
            $this->cache->set(key: $cacheKey, value: $value, ttl: $this->ttl);
        } catch (InvalidArgumentException) {
            // Cache write failure is non-critical.
        }

        return $value;
    }

    public function clear(string $key): void
    {
        try {
            $this->cache->delete(key: $this->cacheKey($key));
        } catch (InvalidArgumentException) {
            // Cache delete failure is non-critical.
        }
    }

    private function cacheKey(string $key): string
    {
        return sprintf('%s.v%d.%s', $this->cacheNamespace, $this->cacheVersion, $key);
    }
}
