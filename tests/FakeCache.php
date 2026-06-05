<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @internal
 *
 * Minimal PSR-16 implementation for tests.
 * Only implements the methods actually used by CachedSettingsProvider.
 * getMultiple/setMultiple/deleteMultiple are required by the interface
 * but are intentionally simplified — Psalm's strict iterable analysis
 * makes a full implementation verbose without adding test value.
 */
final class FakeCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, int|\DateInterval|null> */
    private array $ttls = [];

    /**
     * @return int|\DateInterval|null
     */
    public function getLastTtl(string $key): mixed
    {
        return $this->ttls[$key] ?? null;
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    #[\Override]
    public function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
    {
        $this->data[$key] = $value;
        $this->ttls[$key] = $ttl;

        return true;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        unset($this->data[$key]);

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        $this->data = [];
        $this->ttls = [];

        return true;
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->data[$key] ?? $default;
        }

        return $result;
    }

    #[\Override]
    public function setMultiple(iterable $values, int|\DateInterval|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->data[(string) $key] = $value;
        }

        return true;
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }

        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return array_key_exists(key: $key, array: $this->data);
    }
}

/**
 * @internal
 */
final class ThrowingCache implements CacheInterface
{
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        throw new class extends \Exception implements InvalidArgumentException {};
    }

    #[\Override]
    public function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
    {
        throw new class extends \Exception implements InvalidArgumentException {};
    }

    #[\Override]
    public function delete(string $key): bool
    {
        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        return true;
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return [];
    }

    #[\Override]
    public function setMultiple(iterable $values, int|\DateInterval|null $ttl = null): bool
    {
        return true;
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return false;
    }
}
