<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\SettingTypeMismatchException;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingKey;
use Rasuvaeff\Yii3Settings\Settings;
use Rasuvaeff\Yii3Settings\SettingType;

#[CoversClass(Settings::class)]
final class SettingsTest extends TestCase
{
    private Settings $settings;

    #[\Override]
    protected function setUp(): void
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

    #[Test]
    public function stringReturnsStringValue(): void
    {
        $this->assertSame('admin@example.com', $this->settings->string('mail.from'));
    }

    #[Test]
    public function intReturnsIntValue(): void
    {
        $this->assertSame(50, $this->settings->int('orders.max_items'));
    }

    #[Test]
    public function floatReturnsFloatValue(): void
    {
        $this->assertSame(0.0, $this->settings->float('billing.rate'));
    }

    #[Test]
    public function boolReturnsBoolValue(): void
    {
        $this->assertTrue($this->settings->bool('mail.enabled'));
    }

    #[Test]
    public function arrayReturnsArrayValue(): void
    {
        $this->assertSame([], $this->settings->array('app.features'));
    }

    #[Test]
    public function acceptsSettingKeyObjects(): void
    {
        $this->assertSame('admin@example.com', $this->settings->string(new SettingKey('mail.from')));
    }

    #[Test]
    public function returnsDefaultForMissingValue(): void
    {
        $this->assertSame(0.0, $this->settings->float('billing.rate'));
    }

    #[Test]
    public function returnsTypeDefaultForUnknownInNonStrictMode(): void
    {
        $this->assertSame('', $this->settings->string('unknown'));
        $this->assertSame(0, $this->settings->int('unknown'));
        $this->assertSame(0.0, $this->settings->float('unknown'));
        $this->assertFalse($this->settings->bool('unknown'));
        $this->assertSame([], $this->settings->array('unknown'));
    }

    #[Test]
    public function throwsForUnknownInStrictMode(): void
    {
        $settings = new Settings(
            provider: new ConfigSettingsProvider(),
            definitions: [],
            strictMode: true,
        );

        $this->expectException(UnknownSettingException::class);

        $settings->string('unknown');
    }

    #[Test]
    public function hasReturnsTrueForExistingSetting(): void
    {
        $this->assertTrue($this->settings->has('mail.from'));
    }

    #[Test]
    public function hasReturnsFalseForUnknownSetting(): void
    {
        $this->assertFalse($this->settings->has('unknown'));
    }

    #[Test]
    public function stringThrowsOnTypeMismatch(): void
    {
        $this->expectException(SettingTypeMismatchException::class);

        $this->settings->string('orders.max_items');
    }

    #[Test]
    public function intThrowsOnTypeMismatch(): void
    {
        $this->expectException(SettingTypeMismatchException::class);

        $this->settings->int('mail.from');
    }

    #[Test]
    public function floatThrowsOnTypeMismatch(): void
    {
        $this->expectException(SettingTypeMismatchException::class);

        $this->settings->float('mail.from');
    }

    #[Test]
    public function boolThrowsOnTypeMismatch(): void
    {
        $this->expectException(SettingTypeMismatchException::class);

        $this->settings->bool('mail.from');
    }

    #[Test]
    public function arrayThrowsOnTypeMismatch(): void
    {
        $this->expectException(SettingTypeMismatchException::class);

        $this->settings->array('mail.from');
    }

    #[Test]
    public function stringReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => 42]);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::String),
            ],
        );

        $this->assertSame('42', $settings->string('test.key'));
    }

    #[Test]
    public function intReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => '123']);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Int),
            ],
        );

        $this->assertSame(123, $settings->int('test.key'));
    }

    #[Test]
    public function floatReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => '3.14']);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Float),
            ],
        );

        $this->assertSame(3.14, $settings->float('test.key'));
    }

    #[Test]
    public function boolReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => 1]);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Bool),
            ],
        );

        $this->assertTrue($settings->bool('test.key'));
    }

    #[Test]
    public function arrayReturnsCastResultFromProvider(): void
    {
        $provider = new FakeSettingsProvider(values: ['test.key' => 'not-array']);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'test.key' => new SettingDefinition(key: 'test.key', type: SettingType::Array),
            ],
        );

        $this->assertSame(['not-array'], $settings->array('test.key'));
    }

    #[Test]
    public function returnsTypeDefaultWhenDefinitionHasNullDefaultAndNoValue(): void
    {
        $provider = new FakeSettingsProvider(values: []);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'app.name' => new SettingDefinition(key: 'app.name', type: SettingType::String),
            ],
        );

        $this->assertSame('', $settings->string('app.name'));
    }

    #[Test]
    public function returnsDefinitionDefaultOverTypeDefault(): void
    {
        $provider = new FakeSettingsProvider(values: []);

        $settings = new Settings(
            provider: $provider,
            definitions: [
                'app.name' => new SettingDefinition(key: 'app.name', type: SettingType::String, default: 'my-app'),
            ],
        );

        $this->assertSame('my-app', $settings->string('app.name'));
    }
}
