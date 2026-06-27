<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Psr\SimpleCache\CacheInterface;
use Rasuvaeff\Yii3Settings\CachedSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

#[Test]
#[Covers(CachedSettingsProvider::class)]
final class CachedSettingsProviderTest
{
    private const string DEFAULT_CACHE_KEY = 'yii3-settings.v1.mail.from';

    private MemorySimpleCache $cache;

    private CachedSettingsProvider $provider;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->cache = new MemorySimpleCache();
        $inner = new FakeSettingsProvider(values: ['mail.from' => 'admin@example.com']);

        $this->provider = new CachedSettingsProvider(
            inner: $inner,
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
        $cache = new SpyCache();

        $this->providerWith($cache, ttl: 120)->get('test.key');

        Assert::count($cache->setCalls, 1);
        Assert::same($cache->setCalls[0]['key'], 'yii3-settings.v1.test.key');
        Assert::same($cache->setCalls[0]['value'], 'value');
        Assert::same($cache->setCalls[0]['ttl'], 120);
    }

    public function usesDefaultTtlWhenNotProvided(): void
    {
        $cache = new SpyCache();
        $inner = new FakeSettingsProvider(values: ['test.key' => 'value']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
        );

        $provider->get('test.key');

        Assert::count($cache->setCalls, 1);
        Assert::same($cache->setCalls[0]['ttl'], 60);
    }

    public function supportsCustomCacheNamespaceAndVersion(): void
    {
        $cache = new MemorySimpleCache();
        $inner = new FakeSettingsProvider(values: ['test.key' => 'value']);
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
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['unknown' => 'should-not-reach']),
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
    }

    public function fallsThroughToInnerWhenCacheGetThrows(): void
    {
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['test.key' => 'fallback']),
            cache: new ThrowingCache(),
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
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['mail.from' => 'admin@example.com']),
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
    }

    public function removeThrowsWhenInnerIsReadOnly(): void
    {
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['mail.from' => 'admin@example.com']),
            cache: new MemorySimpleCache(),
            definitions: ['mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String)],
            ttl: 60,
        );

        Expect::exception(\LogicException::class);

        $provider->remove('mail.from');
    }

    private function providerWith(CacheInterface $cache, int $ttl): CachedSettingsProvider
    {
        return new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['test.key' => 'value']),
            cache: $cache,
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
            ttl: $ttl,
        );
    }
}
