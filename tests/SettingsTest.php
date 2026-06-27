<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\SettingTypeMismatchException;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingKey;
use Rasuvaeff\Yii3Settings\Settings;
use Rasuvaeff\Yii3Settings\SettingType;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(Settings::class)]
final class SettingsTest
{
    private Settings $settings;

    #[BeforeTest]
    public function setUp(): void
    {
        $provider = new ConfigSettingsProvider(
            definitions: [
                'mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String, default: 'noreply@example.com'),
                'orders.max_items' => new SettingDefinition(key: 'orders.max_items', type: SettingType::Int, default: 100),
                'billing.rate' => new SettingDefinition(key: 'billing.rate', type: SettingType::Float, default: 0.0),
                'mail.enabled' => new SettingDefinition(key: 'mail.enabled', type: SettingType::Bool, default: true),
                'app.features' => new SettingDefinition(key: 'app.features', type: SettingType::Array, default: []),
            ],
            values: [
                'mail.from' => 'admin@example.com',
                'orders.max_items' => 50,
            ],
        );

        $this->settings = new Settings(
            provider: $provider,
            definitions: $provider->getDefinitions(),
        );
    }

    public function stringReturnsStringValue(): void
    {
        Assert::same($this->settings->string('mail.from'), 'admin@example.com');
    }

    public function intReturnsIntValue(): void
    {
        Assert::same($this->settings->int('orders.max_items'), 50);
    }

    public function floatReturnsFloatValue(): void
    {
        Assert::same($this->settings->float('billing.rate'), 0.0);
    }

    public function boolReturnsBoolValue(): void
    {
        Assert::true($this->settings->bool('mail.enabled'));
    }

    public function arrayReturnsArrayValue(): void
    {
        Assert::same($this->settings->array('app.features'), []);
    }

    public function acceptsSettingKeyObjects(): void
    {
        Assert::same($this->settings->string(new SettingKey('mail.from')), 'admin@example.com');
    }

    public function returnsDefaultForMissingValue(): void
    {
        Assert::same($this->settings->float('billing.rate'), 0.0);
    }

    public function returnsTypeDefaultForUnknownInNonStrictMode(): void
    {
        Assert::same($this->settings->string('unknown'), '');
        Assert::same($this->settings->int('unknown'), 0);
        Assert::same($this->settings->float('unknown'), 0.0);
        Assert::false($this->settings->bool('unknown'));
        Assert::same($this->settings->array('unknown'), []);
    }

    public function throwsForUnknownInStrictMode(): void
    {
        $settings = new Settings(
            provider: new ConfigSettingsProvider(),
            definitions: [],
            strictMode: true,
        );

        Expect::exception(UnknownSettingException::class);

        $settings->string('unknown');
    }

    public function hasReturnsTrueForExistingSetting(): void
    {
        Assert::true($this->settings->has('mail.from'));
    }

    public function hasReturnsFalseForUnknownSetting(): void
    {
        Assert::false($this->settings->has('unknown'));
    }

    public function stringThrowsOnTypeMismatch(): void
    {
        Expect::exception(SettingTypeMismatchException::class);

        $this->settings->string('orders.max_items');
    }

    public function intThrowsOnTypeMismatch(): void
    {
        Expect::exception(SettingTypeMismatchException::class);

        $this->settings->int('mail.from');
    }

    public function floatThrowsOnTypeMismatch(): void
    {
        Expect::exception(SettingTypeMismatchException::class);

        $this->settings->float('mail.from');
    }

    public function boolThrowsOnTypeMismatch(): void
    {
        Expect::exception(SettingTypeMismatchException::class);

        $this->settings->bool('mail.from');
    }

    public function arrayThrowsOnTypeMismatch(): void
    {
        Expect::exception(SettingTypeMismatchException::class);

        $this->settings->array('mail.from');
    }

    public function stringReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => 42]);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String),
            ],
        );

        Assert::same($settings->string('test.key'), '42');
    }

    public function intReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => '123']);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Int),
            ],
        );

        Assert::same($settings->int('test.key'), 123);
    }

    public function floatReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => '3.14']);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Float),
            ],
        );

        Assert::same($settings->float('test.key'), 3.14);
    }

    public function boolReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => 1]);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Bool),
            ],
        );

        Assert::true($settings->bool('test.key'));
    }

    public function arrayReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => 'not-array']);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Array),
            ],
        );

        Assert::same($settings->array('test.key'), ['not-array']);
    }

    public function returnsTypeDefaultWhenDefinitionHasNullDefaultAndNoValue(): void
    {
        $provider = new FakeSettingsProvider(values: []);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'app.name' => new SettingDefinition(key: 'app.name', type: SettingType::String),
            ],
        );

        Assert::same($settings->string('app.name'), '');
    }

    public function returnsDefinitionDefaultOverTypeDefault(): void
    {
        $provider = new FakeSettingsProvider(values: []);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'app.name' => new SettingDefinition(key: 'app.name', type: SettingType::String, default: 'my-app'),
            ],
        );

        Assert::same($settings->string('app.name'), 'my-app');
    }
}
