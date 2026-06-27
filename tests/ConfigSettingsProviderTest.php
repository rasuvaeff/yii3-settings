<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\ConfigSettingsProvider;
use Rasuvaeff\Yii3Settings\Exception\UnknownSettingException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(ConfigSettingsProvider::class)]
final class ConfigSettingsProviderTest
{
    private ConfigSettingsProvider $provider;

    #[BeforeTest]
    public function setUp(): void
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

    public function hasReturnsTrueForDefinedSetting(): void
    {
        Assert::true($this->provider->has('mail.from'));
    }

    public function hasReturnsFalseForUnknownSetting(): void
    {
        Assert::false($this->provider->has('unknown'));
    }

    public function getReturnsConfiguredValue(): void
    {
        Assert::same($this->provider->get('mail.from'), 'admin@example.com');
    }

    public function getReturnsDefaultValueWhenNoExplicitValue(): void
    {
        Assert::same($this->provider->get('orders.max_items'), 100);
    }

    public function throwsForUnknownSetting(): void
    {
        Expect::exception(UnknownSettingException::class);

        $this->provider->get('unknown');
    }

    public function getDefinitionsReturnsDefinitions(): void
    {
        $definitions = $this->provider->getDefinitions();

        Assert::array($definitions)->hasKeys('mail.from');
        Assert::instanceOf($definitions['mail.from'], SettingDefinition::class);
    }

    public function castsConfiguredValuesUsingDefinitionType(): void
    {
        Assert::same($this->provider->get('port'), 8080);
    }

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

        Assert::instanceOf($definitions['mail.from'], SettingDefinition::class);
        Assert::same($provider->get('mail.from'), 'admin@example.com');
    }

    public function normalizeDefinitionsConvertsArrayConfig(): void
    {
        $definitions = ConfigSettingsProvider::normalizeDefinitions([
            'orders.max_items' => ['type' => 'int', 'default' => 100],
        ]);

        Assert::instanceOf($definitions['orders.max_items'], SettingDefinition::class);
        Assert::same($definitions['orders.max_items']->type, SettingType::Int);
    }

    public function supportsNullDefaults(): void
    {
        $provider = new ConfigSettingsProvider(
            definitions: [
                'nullable' => ['type' => 'string', 'default' => null],
            ],
        );

        Assert::null($provider->get('nullable'));
    }
}
