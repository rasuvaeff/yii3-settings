<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\CachedSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;

#[CoversClass(CachedSettingsProvider::class)]
final class CachedSettingsProviderTest extends TestCase
{
    private const string DEFAULT_CACHE_KEY = 'yii3-settings:v1:mail.from';

    private FakeCache $cache;

    private CachedSettingsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = new FakeCache();
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
        $value = $this->provider->get('mail.from');

        $this->assertSame('admin@example.com', $value);
    }

    #[Test]
    public function getCachesValueForSubsequentCalls(): void
    {
        $this->provider->get('mail.from');

        $this->assertTrue($this->cache->has(self::DEFAULT_CACHE_KEY));
    }

    #[Test]
    public function getReturnsCachedValueOnSecondCall(): void
    {
        $this->provider->get('mail.from');

        $cached = $this->cache->get(self::DEFAULT_CACHE_KEY);

        $this->assertSame('admin@example.com', $cached);
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
    public function cachesValueWithConfiguredTtl(): void
    {
        $this->provider->get('mail.from');

        $this->assertSame(60, $this->cache->getLastTtl(self::DEFAULT_CACHE_KEY));
    }

    #[Test]
    public function usesSpecificTtlValue(): void
    {
        $cache = new FakeCache();
        $inner = new FakeSettingsProvider(values: ['test.key' => 'value']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String),
            ],
            ttl: 120,
        );

        $provider->get('test.key');

        $this->assertSame(120, $cache->getLastTtl('yii3-settings:v1:test.key'));
    }

    #[Test]
    public function usesDefaultTtlWhenNotProvided(): void
    {
        $cache = new FakeCache();
        $inner = new FakeSettingsProvider(values: ['test.key' => 'value']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String),
            ],
        );

        $provider->get('test.key');

        $this->assertSame(60, $cache->getLastTtl('yii3-settings:v1:test.key'));
    }

    #[Test]
    public function supportsCustomCacheNamespaceAndVersion(): void
    {
        $cache = new FakeCache();
        $inner = new FakeSettingsProvider(values: ['test.key' => 'value']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String),
            ],
            cacheNamespace: 'app-settings',
            cacheVersion: 3,
        );

        $provider->get('test.key');

        $this->assertTrue($cache->has('app-settings:v3:test.key'));
    }

    #[Test]
    public function getThrowsEarlyForUnknownSetting(): void
    {
        $cache = new FakeCache();
        $inner = new FakeSettingsProvider(values: ['unknown' => 'should-not-reach']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
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
        $cache = new ThrowingCache();
        $inner = new FakeSettingsProvider(values: ['test.key' => 'fallback']);
        $provider = new CachedSettingsProvider(
            inner: $inner,
            cache: $cache,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String),
            ],
            ttl: 60,
        );

        $this->assertSame('fallback', $provider->get('test.key'));
    }
}
