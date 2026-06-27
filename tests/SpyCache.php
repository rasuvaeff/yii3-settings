<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 cache spy that records `set()` calls for assertion. Reads return the
 * configured default (null by default) so the provider falls through to inner.
 *
 * @internal
 */
final class SpyCache implements CacheInterface
{
    /** @var list<array{key: string, value: mixed, ttl: int|\DateInterval|null}> */
    public array $setCalls = [];

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    #[\Override]
    public function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool
    {
        $this->setCalls[] = ['key' => $key, 'value' => $value, 'ttl' => $ttl];

        return true;
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
