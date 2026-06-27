<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\SettingState;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SettingState::class)]
final class SettingStateTest
{
    public function holdsAllProperties(): void
    {
        $state = new SettingState(
            key: 'mail.from',
            effectiveValue: 'noreply@example.com',
            hasStoredOverride: true,
            source: 'db',
            isSecret: false,
            isWritable: true,
        );

        Assert::same($state->key, 'mail.from');
        Assert::same($state->effectiveValue, 'noreply@example.com');
        Assert::true($state->hasStoredOverride);
        Assert::same($state->source, 'db');
        Assert::false($state->isSecret);
        Assert::true($state->isWritable);
    }

    public function secretSettingWithOverride(): void
    {
        $state = new SettingState(
            key: 'billing.stripe_key',
            effectiveValue: null,
            hasStoredOverride: true,
            source: 'db',
            isSecret: true,
            isWritable: true,
        );

        Assert::true($state->isSecret);
        Assert::true($state->hasStoredOverride);
        Assert::null($state->effectiveValue);
    }

    public function configSourceSetting(): void
    {
        $state = new SettingState(
            key: 'app.name',
            effectiveValue: 'MyApp',
            hasStoredOverride: false,
            source: 'config',
            isSecret: false,
            isWritable: false,
        );

        Assert::same($state->source, 'config');
        Assert::false($state->hasStoredOverride);
        Assert::false($state->isWritable);
    }

    public function defaultSourceSetting(): void
    {
        $state = new SettingState(
            key: 'orders.max_items',
            effectiveValue: 100,
            hasStoredOverride: false,
            source: 'default',
            isSecret: false,
            isWritable: true,
        );

        Assert::same($state->source, 'default');
        Assert::same($state->effectiveValue, 100);
    }
}
