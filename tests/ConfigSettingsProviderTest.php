<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;

#[CoversClass(ConfigSettingsProvider::class)]
final class ConfigSettingsProviderTest extends TestCase
{
    private ConfigSettingsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ConfigSettingsProvider(
            definitions: [
                'mail.from' => new SettingDefinition(key: 'mail.from', type: SettingType::String, default: 'noreply@example.com'),
                'orders.max_items' => new SettingDefinition(key: 'orders.max_items', type: SettingType::Int, default: 100),
                'port' => new SettingDefinition(key: 'port', type: SettingType::Int),
            ],
            values: [
                'mail.from' => 'admin@example.com',
                'port' => '8080',
            ],
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
    public function getReturnsConfiguredValue(): void
    {
        $this->assertSame('admin@example.com', $this->provider->get('mail.from'));
    }

    #[Test]
    public function getReturnsDefaultValueWhenNoExplicitValue(): void
    {
        $this->assertSame(100, $this->provider->get('orders.max_items'));
    }

    #[Test]
    public function throwsForUnknownSetting(): void
    {
        $this->expectException(UnknownSettingException::class);

        $this->provider->get('unknown');
    }

    #[Test]
    public function getDefinitionsReturnsDefinitions(): void
    {
        $definitions = $this->provider->getDefinitions();

        $this->assertArrayHasKey('mail.from', $definitions);
        $this->assertInstanceOf(SettingDefinition::class, $definitions['mail.from']);
    }

    #[Test]
    public function castsConfiguredValuesUsingDefinitionType(): void
    {
        $this->assertSame(8080, $this->provider->get('port'));
    }

    #[Test]
    public function normalizesArrayDefinitionsFromConfig(): void
    {
        $provider = new ConfigSettingsProvider(
            definitions: [
                'mail.from' => ['type' => 'string', 'default' => 'noreply@example.com'],
            ],
            values: [
                'mail.from' => 'admin@example.com',
            ],
        );

        $definitions = $provider->getDefinitions();

        $this->assertInstanceOf(SettingDefinition::class, $definitions['mail.from']);
        $this->assertSame('admin@example.com', $provider->get('mail.from'));
    }

    #[Test]
    public function normalizeDefinitionsConvertsArrayConfig(): void
    {
        $definitions = ConfigSettingsProvider::normalizeDefinitions([
            'orders.max_items' => ['type' => 'int', 'default' => 100],
        ]);

        $this->assertInstanceOf(SettingDefinition::class, $definitions['orders.max_items']);
        $this->assertSame(SettingType::Int, $definitions['orders.max_items']->type);
    }

    #[Test]
    public function supportsNullDefaults(): void
    {
        $provider = new ConfigSettingsProvider(
            definitions: [
                'nullable' => ['type' => 'string', 'default' => null],
            ],
        );

        $this->assertNull($provider->get('nullable'));
    }
}
