<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3Settings\CachedSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingsProvider;
use Rasuvaeff\Yii3Settings\SettingType;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

use function Rasuvaeff\Understudy\verify;
use function Rasuvaeff\Understudy\when;

#[Test]
#[Covers(CachedSettingsProvider::class)]
final class CachedSettingsProviderTest
{
    private const string DEFAULT_CACHE_KEY = 'yii3-settings.v1.mail.from';

    private MemorySimpleCache $cache;

    private SettingsProvider $inner;

    private CachedSettingsProvider $provider;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->cache = new MemorySimpleCache();
        $this->inner = Understudy::for(SettingsProvider::class);
        when(fn() => $this->inner->get('mail.from'))->returns('admin@example.com');

        $this->provider = new CachedSettingsProvider(
            inner: $this->inner,
            cache: $this->cache,
            definitions: [
                'mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String),
                'missing' => new SettingDefinition(key: 'missing', type: SettingType::String),
            ],
            ttl: 60,
        );
    }

    public function hasReturnsTrueForDefinedSetting(): void
    {
        Assert::true($this->provider->has('mail.from'));
    }

    public function hasReturnsFalseForUnknownSetting(): void
    {
        Assert::false($this->provider->has('unknown'));
    }

    public function getReturnsValueFromInnerOnFirstCall(): void
    {
        Assert::same($this->provider->get('mail.from'), 'admin@example.com');
    }

    public function getCachesValueForSubsequentCalls(): void
    {
        $this->provider->get('mail.from');

        Assert::same($this->cache->get(self::DEFAULT_CACHE_KEY), 'admin@example.com');
    }

    public function returnsCachedValueInsteadOfInner(): void
    {
        $this->cache->set(self::DEFAULT_CACHE_KEY, 'cached@example.com');

        Assert::same($this->provider->get('mail.from'), 'cached@example.com');

        Understudy::unused($this->inner);
    }

    public function throwsForUnknownSetting(): void
    {
        Expect::exception(UnknownSettingException::class);

        $this->provider->get('unknown');
    }

    public function clearRemovesCachedValue(): void
    {
        $this->provider->get('mail.from');
        $this->provider->clear('mail.from');

        Assert::false($this->cache->has(self::DEFAULT_CACHE_KEY));
    }

    public function usesConfiguredTtl(): void
    {
        $cache = Understudy::for(CacheInterface::class);

        $this->providerWith($cache, ttl: 120)->get('test.key');

        verify(fn() => $cache->set('yii3-settings.v1.test.key', 'value', 120), times: 1);
    }

    public function usesDefaultTtlWhenNotProvided(): void
    {
        $cache = Understudy::for(CacheInterface::class);
        $inner = Understudy::for(SettingsProvider::class);
        when(fn() => $inner->get('test.key'))->returns('value');
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
        );

        $provider->get('test.key');

        verify(fn() => $cache->set('yii3-settings.v1.test.key', 'value', 60), times: 1);
    }

    public function supportsCustomCacheNamespaceAndVersion(): void
    {
        $cache = new MemorySimpleCache();
        $inner = Understudy::for(SettingsProvider::class);
        when(fn() => $inner->get('test.key'))->returns('value');
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
            cacheNamespace: 'app-settings',
            cacheVersion: 3,
        );

        $provider->get('test.key');

        Assert::true($cache->has('app-settings.v3.test.key'));
    }

    public function getThrowsEarlyForUnknownSetting(): void
    {
        $inner = Understudy::for(SettingsProvider::class);
        when(fn() => $inner->get('unknown'))->returns('should-not-reach');
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: new MemorySimpleCache(),
            definitions: [],
            ttl: 60,
        );

        try {
            $provider->get('unknown');
            Assert::fail('Expected UnknownSettingException');
        } catch (UnknownSettingException $e) {
            Assert::string($e->getMessage())->contains('Unknown setting "unknown"');
        }

        Understudy::unused($inner);
    }

    public function fallsThroughToInnerWhenCacheGetThrows(): void
    {
        $cache = Understudy::for(CacheInterface::class);
        when(fn() => $cache->get(Arg::any()))->throws(
            new class extends \Exception implements InvalidArgumentException {},
        );
        $inner = Understudy::for(SettingsProvider::class);
        when(fn() => $inner->get('test.key'))->returns('fallback');
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
            ttl: 60,
        );

        Assert::same($provider->get('test.key'), 'fallback');
    }

    public function setDelegatesToInnerAndInvalidatesCache(): void
    {
        $cache = new MemorySimpleCache();
        $inner = new FakeWritableSettingsProvider(values: ['mail.from' => 'old@example.com']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String)],
            ttl: 60,
        );

        // Warm the cache with the old value.
        Assert::same($provider->get('mail.from'), 'old@example.com');
        Assert::true($cache->has(self::DEFAULT_CACHE_KEY));

        $provider->set('mail.from', 'new@example.com');

        // Cache entry is invalidated and the inner provider holds the new value.
        Assert::false($cache->has(self::DEFAULT_CACHE_KEY));
        Assert::same($inner->values()['mail.from'], 'new@example.com');
        Assert::same($provider->get('mail.from'), 'new@example.com');
    }

    public function removeDelegatesToInnerAndInvalidatesCache(): void
    {
        $cache = new MemorySimpleCache();
        $inner = new FakeWritableSettingsProvider(values: ['mail.from' => 'old@example.com']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String)],
            ttl: 60,
        );

        $provider->get('mail.from');
        Assert::true($cache->has(self::DEFAULT_CACHE_KEY));

        $provider->remove('mail.from');

        Assert::false($cache->has(self::DEFAULT_CACHE_KEY));
        Assert::false($inner->has('mail.from'));
    }

    public function setThrowsWhenInnerIsReadOnly(): void
    {
        $inner = Understudy::for(SettingsProvider::class);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: new MemorySimpleCache(),
            definitions: ['mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String)],
            ttl: 60,
        );

        try {
            $provider->set('mail.from', 'new@example.com');
            Assert::fail('Expected LogicException');
        } catch (\LogicException $e) {
            Assert::string($e->getMessage())->contains('inner provider is read-only');
        }

        Understudy::unused($inner);
    }

    public function removeThrowsWhenInnerIsReadOnly(): void
    {
        $inner = Understudy::strict(Understudy::for(SettingsProvider::class));
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: new MemorySimpleCache(),
            definitions: ['mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String)],
            ttl: 60,
        );

        Expect::exception(\LogicException::class);

        $provider->remove('mail.from');
    }

    private function providerWith(CacheInterface $cache, int $ttl): CachedSettingsProvider
    {
        $inner = Understudy::for(SettingsProvider::class);
        when(fn() => $inner->get('test.key'))->returns('value');

        return new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
            ttl: $ttl,
        );
    }
}
