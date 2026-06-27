<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\ChainSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(ChainSettingsProvider::class)]
final class ChainSettingsProviderTest
{
    public function checksProvidersInOrder(): void
    {
        $primary = new FakeSettingsProvider(values: ['key1' => 'from-primary']);
        $fallback = new FakeSettingsProvider(values: ['key2' => 'from-fallback']);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        Assert::true($chain->has('key1'));
        Assert::true($chain->has('key2'));
        Assert::false($chain->has('key3'));
    }

    public function returnsValueFromFirstProvider(): void
    {
        $primary = new FakeSettingsProvider(values: ['key1' => 'primary']);
        $fallback = new FakeSettingsProvider(values: ['key1' => 'fallback']);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        Assert::same($chain->get('key1'), 'primary');
    }

    public function fallsThroughToNextProvider(): void
    {
        $primary = new FakeSettingsProvider(values: []);
        $fallback = new FakeSettingsProvider(values: ['key1' => 'fallback']);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        Assert::same($chain->get('key1'), 'fallback');
    }

    public function throwsWhenNoProviderHasValue(): void
    {
        $chain = new ChainSettingsProvider(providers: []);

        Expect::exception(UnknownSettingException::class);

        $chain->get('unknown');
    }

    public function emptyChainHasNothing(): void
    {
        $chain = new ChainSettingsProvider(providers: []);

        Assert::false($chain->has('any'));
    }
}
