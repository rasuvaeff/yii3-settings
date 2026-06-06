<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\SettingState;

#[CoversClass(SettingState::class)]
final class SettingStateTest extends TestCase
{
    #[Test]
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

        $this->assertSame('mail.from', $state->key);
        $this->assertSame('noreply@example.com', $state->effectiveValue);
        $this->assertTrue($state->hasStoredOverride);
        $this->assertSame('db', $state->source);
        $this->assertFalse($state->isSecret);
        $this->assertTrue($state->isWritable);
    }

    #[Test]
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

        $this->assertTrue($state->isSecret);
        $this->assertTrue($state->hasStoredOverride);
        $this->assertNull($state->effectiveValue);
    }

    #[Test]
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

        $this->assertSame('config', $state->source);
        $this->assertFalse($state->hasStoredOverride);
        $this->assertFalse($state->isWritable);
    }

    #[Test]
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

        $this->assertSame('default', $state->source);
        $this->assertSame(100, $state->effectiveValue);
    }
}
