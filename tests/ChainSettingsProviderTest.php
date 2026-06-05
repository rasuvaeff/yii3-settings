<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\ChainSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;

#[CoversClass(ChainSettingsProvider::class)]
final class ChainSettingsProviderTest extends TestCase
{
    #[Test]
    public function checksProvidersInOrder(): void
    {
        $primary = new FakeSettingsProvider(values: ['key1' => 'from-primary']);
        $fallback = new FakeSettingsProvider(values: ['key2' => 'from-fallback']);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        $this->assertTrue($chain->has('key1'));
        $this->assertTrue($chain->has('key2'));
        $this->assertFalse($chain->has('key3'));
    }

    #[Test]
    public function returnsValueFromFirstProvider(): void
    {
        $primary = new FakeSettingsProvider(values: ['key1' => 'primary']);
        $fallback = new FakeSettingsProvider(values: ['key1' => 'fallback']);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        $this->assertSame('primary', $chain->get('key1'));
    }

    #[Test]
    public function fallsThroughToNextProvider(): void
    {
        $primary = new FakeSettingsProvider(values: []);
        $fallback = new FakeSettingsProvider(values: ['key1' => 'fallback']);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        $this->assertSame('fallback', $chain->get('key1'));
    }

    #[Test]
    public function throwsWhenNoProviderHasValue(): void
    {
        $chain = new ChainSettingsProvider(providers: []);

        $this->expectException(UnknownSettingException::class);

        $chain->get('unknown');
    }

    #[Test]
    public function emptyChainHasNothing(): void
    {
        $chain = new ChainSettingsProvider(providers: []);

        $this->assertFalse($chain->has('any'));
    }
}
