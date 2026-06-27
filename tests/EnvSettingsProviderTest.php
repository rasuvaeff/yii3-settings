<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\EnvSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(EnvSettingsProvider::class)]
final class EnvSettingsProviderTest
{
    private EnvSettingsProvider $provider;

    #[BeforeTest]
    public function setUp(): void
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

    #[AfterTest]
    public function tearDown(): void
    {
        putenv('TEST_APP_SETTING_MAIL_ENABLED');
        putenv('TEST_APP_SETTING_ORDERS_MAX_ITEMS');
    }

    public function hasReturnsTrueWhenEnvIsSet(): void
    {
        Assert::true($this->provider->has('mail.enabled'));
    }

    public function hasReturnsFalseWhenEnvNotSet(): void
    {
        Assert::false($this->provider->has('unknown.setting'));
    }

    public function hasReturnsFalseForUnknownDefinition(): void
    {
        Assert::false($this->provider->has('unknown'));
    }

    public function getReturnsCastedEnvValue(): void
    {
        Assert::same($this->provider->get('orders.max_items'), 50);
    }

    public function hasReturnsFalseForDefinedKeyWithoutEnvVariable(): void
    {
        putenv('TEST_APP_SETTING_ORDERS_MAX_ITEMS');

        Assert::false($this->provider->has('orders.max_items'));
    }

    public function getThrowsForUnknownDefinition2(): void
    {
        $provider = new EnvSettingsProvider(
            definitions: [],
            prefix: 'TEST_APP_SETTING_',
        );

        try {
            $provider->get('nonexistent');
            Assert::fail('Expected UnknownSettingException');
        } catch (UnknownSettingException $e) {
            Assert::string($e->getMessage())->contains('Unknown setting "nonexistent"');
        }
    }

    public function getThrowsForDefinedKeyWithoutEnvVariable(): void
    {
        $provider = new EnvSettingsProvider(
            definitions: [
                'no.env' => new SettingDefinition(key: 'no.env', type: SettingType::String),
            ],
            prefix: 'TEST_APP_SETTING_',
        );

        try {
            $provider->get('no.env');
            Assert::fail('Expected UnknownSettingException');
        } catch (UnknownSettingException $e) {
            Assert::string($e->getMessage())->contains('Environment variable for setting "no.env" not found');
        }
    }
}
