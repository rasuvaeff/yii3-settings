<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\EnvSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;

#[CoversClass(EnvSettingsProvider::class)]
final class EnvSettingsProviderTest extends TestCase
{
    private EnvSettingsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        putenv('TEST_APP_SETTING_MAIL_ENABLED=true');
        putenv('TEST_APP_SETTING_ORDERS_MAX_ITEMS=50');

        $this->provider = new EnvSettingsProvider(
            definitions: [
                'mail.enabled' => new SettingDefinition(key: 'mail.enabled', type: SettingType::Bool),
                'orders.max_items' => new SettingDefinition(key: 'orders.max_items', type: SettingType::Int),
            ],
            prefix: 'TEST_APP_SETTING_',
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        putenv('TEST_APP_SETTING_MAIL_ENABLED');
        putenv('TEST_APP_SETTING_ORDERS_MAX_ITEMS');
    }

    #[Test]
    public function hasReturnsTrueWhenEnvIsSet(): void
    {
        $this->assertTrue($this->provider->has('mail.enabled'));
    }

    #[Test]
    public function hasReturnsFalseWhenEnvNotSet(): void
    {
        $this->assertFalse($this->provider->has('unknown.setting'));
    }

    #[Test]
    public function hasReturnsFalseForUnknownDefinition(): void
    {
        $this->assertFalse($this->provider->has('unknown'));
    }

    #[Test]
    public function getReturnsCastedEnvValue(): void
    {
        $this->assertSame(50, $this->provider->get('orders.max_items'));
    }

    #[Test]
    public function hasReturnsFalseForDefinedKeyWithoutEnvVariable(): void
    {
        putenv('TEST_APP_SETTING_ORDERS_MAX_ITEMS');

        $this->assertFalse($this->provider->has('orders.max_items'));
    }

    #[Test]
    public function getThrowsForUnknownDefinition2(): void
    {
        $provider = new EnvSettingsProvider(
            definitions: [],
            prefix: 'TEST_APP_SETTING_',
        );

        $this->expectException(UnknownSettingException::class);
        $this->expectExceptionMessage('Unknown setting "nonexistent"');

        $provider->get('nonexistent');
    }

    #[Test]
    public function getThrowsForDefinedKeyWithoutEnvVariable(): void
    {
        $provider = new EnvSettingsProvider(
            definitions: [
                'no.env' => new SettingDefinition(key: 'no.env', type: SettingType::String),
            ],
            prefix: 'TEST_APP_SETTING_',
        );

        $this->expectException(UnknownSettingException::class);
        $this->expectExceptionMessage('Environment variable for setting "no.env" not found');

        $provider->get('no.env');
    }
}
