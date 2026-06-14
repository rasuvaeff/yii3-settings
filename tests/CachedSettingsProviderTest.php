<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Rasuvaeff\Yii3Settings\CachedSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

#[CoversClass(CachedSettingsProvider::class)]
final class CachedSettingsProviderTest extends TestCase
{
    private const string DEFAULT_CACHE_KEY = 'yii3-settings.v1.mail.from';

    private MemorySimpleCache $cache;

    private CachedSettingsProvider $provider;

    #[\Override]
    protected function setUp(): void
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

    #[Test]
    public function hasReturnsTrueForDefinedSetting(): void
    {
        $this->assertTrue($this->provider->has('mail.from'));
    }

    #[Test]
    public function hasReturnsFalseForUnknownSetting(): void
    {
        $this->assertFalse($this->provider->has('unknown'));
    }

    #[Test]
    public function getReturnsValueFromInnerOnFirstCall(): void
    {
        $this->assertSame('admin@example.com', $this->provider->get('mail.from'));
    }

    #[Test]
    public function getCachesValueForSubsequentCalls(): void
    {
        $this->provider->get('mail.from');

        $this->assertSame('admin@example.com', $this->cache->get(self::DEFAULT_CACHE_KEY));
    }

    #[Test]
    public function returnsCachedValueInsteadOfInner(): void
    {
        $this->cache->set(self::DEFAULT_CACHE_KEY, 'cached@example.com');

        $this->assertSame('cached@example.com', $this->provider->get('mail.from'));
    }

    #[Test]
    public function throwsForUnknownSetting(): void
    {
        $this->expectException(UnknownSettingException::class);

        $this->provider->get('unknown');
    }

    #[Test]
    public function clearRemovesCachedValue(): void
    {
        $this->provider->get('mail.from');
        $this->provider->clear('mail.from');

        $this->assertFalse($this->cache->has(self::DEFAULT_CACHE_KEY));
    }

    #[Test]
    public function usesConfiguredTtl(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())->method('set')->with(
            key: 'yii3-settings.v1.test.key',
            value: 'value',
            ttl: 120,
        );

        $this->providerWith($cache, ttl: 120)->get('test.key');
    }

    #[Test]
    public function usesDefaultTtlWhenNotProvided(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())->method('set')->with(
            key: 'yii3-settings.v1.test.key',
            value: 'value',
            ttl: 60,
        );

        $inner = new FakeSettingsProvider(values: ['test.key' => 'value']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
        );

        $provider->get('test.key');
    }

    #[Test]
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

        $this->assertTrue($cache->has('app-settings.v3.test.key'));
    }

    #[Test]
    public function getThrowsEarlyForUnknownSetting(): void
    {
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['unknown' => 'should-not-reach']),
            cache: new MemorySimpleCache(),
            definitions: [],
            ttl: 60,
        );

        $this->expectException(UnknownSettingException::class);
        $this->expectExceptionMessage('Unknown setting "unknown"');

        $provider->get('unknown');
    }

    #[Test]
    public function fallsThroughToInnerWhenCacheGetThrows(): void
    {
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['test.key' => 'fallback']),
            cache: new ThrowingCache(),
            definitions: ['test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String)],
            ttl: 60,
        );

        $this->assertSame('fallback', $provider->get('test.key'));
    }

    #[Test]
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
        $this->assertSame('old@example.com', $provider->get('mail.from'));
        $this->assertTrue($cache->has(self::DEFAULT_CACHE_KEY));

        $provider->set('mail.from', 'new@example.com');

        // Cache entry is invalidated and the inner provider holds the new value.
        $this->assertFalse($cache->has(self::DEFAULT_CACHE_KEY));
        $this->assertSame('new@example.com', $inner->values()['mail.from']);
        $this->assertSame('new@example.com', $provider->get('mail.from'));
    }

    #[Test]
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
        $this->assertTrue($cache->has(self::DEFAULT_CACHE_KEY));

        $provider->remove('mail.from');

        $this->assertFalse($cache->has(self::DEFAULT_CACHE_KEY));
        $this->assertFalse($inner->has('mail.from'));
    }

    #[Test]
    public function setThrowsWhenInnerIsReadOnly(): void
    {
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['mail.from' => 'admin@example.com']),
            cache: new MemorySimpleCache(),
            definitions: ['mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String)],
            ttl: 60,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('inner provider is read-only');

        $provider->set('mail.from', 'new@example.com');
    }

    #[Test]
    public function removeThrowsWhenInnerIsReadOnly(): void
    {
        $provider = new CachedSettingsProvider(
            inner: new FakeSettingsProvider(values: ['mail.from' => 'admin@example.com']),
            cache: new MemorySimpleCache(),
            definitions: ['mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String)],
            ttl: 60,
        );

        $this->expectException(\LogicException::class);

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
