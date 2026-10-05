<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3Settings\ChainSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingsProvider;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

use function Rasuvaeff\Understudy\verify;
use function Rasuvaeff\Understudy\when;

#[Test]
#[Covers(ChainSettingsProvider::class)]
final class ChainSettingsProviderTest
{
    public function checksProvidersInOrder(): void
    {
        $primary = Understudy::for(SettingsProvider::class);
        when(fn() => $primary->has('key1'))->returns(true);
        $fallback = Understudy::for(SettingsProvider::class);
        when(fn() => $fallback->has('key2'))->returns(true);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        Assert::true($chain->has('key1'));
        Assert::true($chain->has('key2'));
        Assert::false($chain->has('key3'));

        // A key the primary lacks is looked up in the fallback.
        verify(fn() => $primary->has('key2'), times: 1);
        verify(fn() => $fallback->has('key2'), times: 1);
    }

    public function returnsValueFromFirstProvider(): void
    {
        $primary = Understudy::for(SettingsProvider::class);
        when(fn() => $primary->has('key1'))->returns(true);
        when(fn() => $primary->get('key1'))->returns('primary');
        $fallback = Understudy::for(SettingsProvider::class);

        $chain = new ChainSettingsProvider(providers: [$primary, $fallback]);

        Assert::same($chain->get('key1'), 'primary');

        // First match wins: the fallback is never consulted.
        Understudy::unused($fallback);
    }

    public function fallsThroughToNextProvider(): void
    {
        $primary = Understudy::for(SettingsProvider::class);
        when(fn() => $primary->has('key1'))->returns(false);
        $fallback = Understudy::for(SettingsProvider::class);
        when(fn() => $fallback->has('key1'))->returns(true);
        when(fn() => $fallback->get('key1'))->returns('fallback');

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
