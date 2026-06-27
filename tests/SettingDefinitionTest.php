<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingKey;
use Rasuvaeff\Yii3Settings\SettingType;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(SettingDefinition::class)]
final class SettingDefinitionTest
{
    public function createsWithKeyAndType(): void
    {
        $def = new SettingDefinition(key: 'mail.from', type: SettingType::String, default: 'noreply@example.com');

        Assert::same($def->key, 'mail.from');
        Assert::same($def->type, SettingType::String);
        Assert::same($def->default, 'noreply@example.com');
    }

    public function acceptsSettingKeyInstance(): void
    {
        $def = new SettingDefinition(key: new SettingKey('mail.from'), type: SettingType::String);

        Assert::same($def->key, 'mail.from');
        Assert::same($def->settingKey->toString(), 'mail.from');
    }

    public function buildsFromArrayConfig(): void
    {
        $def = SettingDefinition::fromConfig('orders.max_items', ['type' => 'int', 'default' => 100]);

        Assert::same($def->key, 'orders.max_items');
        Assert::same($def->type, SettingType::Int);
        Assert::same($def->default, 100);
    }

    public function createsWithoutDefault(): void
    {
        $def = new SettingDefinition(key: 'billing.currency', type: SettingType::String);

        Assert::null($def->default);
        Assert::false($def->hasDefault());
    }

    public function hasDefaultReturnsTrueWhenSet(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::String, default: 'value');

        Assert::true($def->hasDefault());
    }

    public static function invalidKeyProvider(): array
    {
        return [
            'uppercase' => ['INVALID'],
            'starts with number' => ['1key'],
            'starts with dash' => ['-key'],
            'spaces' => ['my key'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidKeyProvider')]
    public function throwsOnInvalidKey(string $key): void
    {
        Expect::exception(\Rasuvaeff\Yii3Settings\Exception\InvalidSettingKeyException::class);

        new SettingDefinition(key: $key, type: SettingType::String);
    }

    public function castsStringToString(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::String);

        Assert::same($def->cast(123), '123');
    }

    public function castsIntToInt(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Int);

        Assert::same($def->cast('42'), 42);
    }

    public function castsFloatToFloat(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Float);

        Assert::same($def->cast('3.14'), 3.14);
    }

    public function castsBoolToBool(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Bool);

        Assert::true($def->cast(1));
        Assert::false($def->cast(0));
    }

    public function castsArrayToArray(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Array);

        Assert::same($def->cast(['a', 'b']), ['a', 'b']);
        Assert::same($def->cast(null), []);
    }

    public function castPreservesExactType(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Int);

        Assert::same($def->cast(42), 42);
    }

    public function secretDefaultsToFalse(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::String);

        Assert::false($def->isSecret());
    }

    public function secretFlagEnabled(): void
    {
        $def = new SettingDefinition(key: 'billing.stripe_key', type: SettingType::String, secret: true);

        Assert::true($def->isSecret());
    }

    public function throwsOnSecretWithNonStringType(): void
    {
        try {
            new SettingDefinition(key: 'test', type: SettingType::Int, secret: true);
            Assert::fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Secret flag is only supported for string type settings');
        }
    }

    public static function nonStringTypeProvider(): array
    {
        return [
            'int' => [SettingType::Int],
            'float' => [SettingType::Float],
            'bool' => [SettingType::Bool],
            'array' => [SettingType::Array],
        ];
    }

    #[DataProvider('nonStringTypeProvider')]
    public function throwsOnSecretWithAnyNonStringType(SettingType $type): void
    {
        Expect::exception(\InvalidArgumentException::class);

        new SettingDefinition(key: 'test', type: $type, secret: true);
    }

    public function fromConfigWithSecretFlag(): void
    {
        $def = SettingDefinition::fromConfig('billing.stripe_key', [
            'type' => 'string',
            'secret' => true,
        ]);

        Assert::true($def->isSecret());
        Assert::same($def->type, SettingType::String);
    }

    public function fromConfigWithoutSecretFlag(): void
    {
        $def = SettingDefinition::fromConfig('mail.from', [
            'type' => 'string',
            'default' => 'noreply@example.com',
        ]);

        Assert::false($def->isSecret());
    }

    public function metadataDefaultsToNull(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::String);

        Assert::null($def->label);
        Assert::null($def->group);
        Assert::null($def->help);
        Assert::null($def->choices);
        Assert::false($def->readonly);
    }

    public function acceptsPresentationMetadata(): void
    {
        $def = new SettingDefinition(
            key: 'orders.status',
            type: SettingType::String,
            label: 'Order status',
            group: 'Orders',
            help: 'Default status for new orders',
            choices: ['new', 'paid'],
            readonly: true,
        );

        Assert::same($def->label, 'Order status');
        Assert::same($def->group, 'Orders');
        Assert::same($def->help, 'Default status for new orders');
        Assert::same($def->choices, ['new', 'paid']);
        Assert::true($def->readonly);
    }

    public function fromConfigDefaultsMetadataWhenOmitted(): void
    {
        $def = SettingDefinition::fromConfig('orders.status', ['type' => 'string']);

        Assert::null($def->label);
        Assert::null($def->group);
        Assert::null($def->help);
        Assert::null($def->choices);
        Assert::false($def->readonly);
    }

    public function fromConfigReadsPresentationMetadata(): void
    {
        $def = SettingDefinition::fromConfig('orders.status', [
            'type' => 'string',
            'label' => 'Order status',
            'group' => 'Orders',
            'help' => 'Default status for new orders',
            'choices' => ['new', 'paid'],
            'readonly' => true,
        ]);

        Assert::same($def->label, 'Order status');
        Assert::same($def->group, 'Orders');
        Assert::same($def->help, 'Default status for new orders');
        Assert::same($def->choices, ['new', 'paid']);
        Assert::true($def->readonly);
    }
}
