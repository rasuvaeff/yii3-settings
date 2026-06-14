<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @api
 */
final readonly class CachedSettingsProvider implements WritableSettingsProvider
{
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
        private int $ttl = 60,
        private string $cacheNamespace = 'yii3-settings',
        private int $cacheVersion = 1,
    ) {
        $this->definitions = ConfigSettingsProvider::normalizeDefinitions($definitions);
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

    /**
     * Persists the value through the inner writable provider and invalidates the
     * cached entry so the next read observes the new value. The cache is cleared
     * only after a successful write.
     */
    #[\Override]
    public function set(string $key, mixed $value): void
    {
        $this->writableInner()->set(key: $key, value: $value);
        $this->clear($key);
    }

    /**
     * Removes the stored override through the inner writable provider and
     * invalidates the cached entry.
     */
    #[\Override]
    public function remove(string $key): void
    {
        $this->writableInner()->remove(key: $key);
        $this->clear($key);
    }

    private function writableInner(): WritableSettingsProvider
    {
        if (!$this->inner instanceof WritableSettingsProvider) {
            throw new \LogicException(
                'CachedSettingsProvider cannot delegate writes: the inner provider is read-only.',
            );
        }

        return $this->inner;
    }

    private function cacheKey(string $key): string
    {
        return sprintf('%s.v%d.%s', $this->cacheNamespace, $this->cacheVersion, $key);
    }
}
