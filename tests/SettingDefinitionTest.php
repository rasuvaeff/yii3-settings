<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\Exception\InvalidSettingKeyException;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingKey;
use Rasuvaeff\Yii3Settings\SettingType;

#[CoversClass(SettingDefinition::class)]
final class SettingDefinitionTest extends TestCase
{
    #[Test]
    public function createsWithKeyAndType(): void
    {
        $def = new SettingDefinition(key: 'mail.from', type: SettingType::String, default: 'noreply@example.com');

        $this->assertSame('mail.from', $def->key);
        $this->assertSame(SettingType::String, $def->type);
        $this->assertSame('noreply@example.com', $def->default);
    }

    #[Test]
    public function acceptsSettingKeyInstance(): void
    {
        $def = new SettingDefinition(key: new SettingKey('mail.from'), type: SettingType::String);

        $this->assertSame('mail.from', $def->key);
        $this->assertSame('mail.from', $def->settingKey->toString());
    }

    #[Test]
    public function buildsFromArrayConfig(): void
    {
        $def = SettingDefinition::fromConfig('orders.max_items', ['type' => 'int', 'default' => 100]);

        $this->assertSame('orders.max_items', $def->key);
        $this->assertSame(SettingType::Int, $def->type);
        $this->assertSame(100, $def->default);
    }

    #[Test]
    public function createsWithoutDefault(): void
    {
        $def = new SettingDefinition(key: 'billing.currency', type: SettingType::String);

        $this->assertNull($def->default);
        $this->assertFalse($def->hasDefault());
    }

    #[Test]
    public function hasDefaultReturnsTrueWhenSet(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::String, default: 'value');

        $this->assertTrue($def->hasDefault());
    }

    /**
     * @return array<string, array{string}>
     */
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
    #[Test]
    public function throwsOnInvalidKey(string $key): void
    {
        $this->expectException(InvalidSettingKeyException::class);

        new SettingDefinition(key: $key, type: SettingType::String);
    }

    #[Test]
    public function castsStringToString(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::String);

        $this->assertSame('123', $def->cast(123));
    }

    #[Test]
    public function castsIntToInt(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Int);

        $this->assertSame(42, $def->cast('42'));
    }

    #[Test]
    public function castsFloatToFloat(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Float);

        $this->assertSame(3.14, $def->cast('3.14'));
    }

    #[Test]
    public function castsBoolToBool(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Bool);

        $this->assertTrue($def->cast(1));
        $this->assertFalse($def->cast(0));
    }

    #[Test]
    public function castsArrayToArray(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Array);

        $this->assertSame(['a', 'b'], $def->cast(['a', 'b']));
        $this->assertSame([], $def->cast(null));
    }

    #[Test]
    public function castPreservesExactType(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::Int);

        $this->assertSame(42, $def->cast(42));
    }

    #[Test]
    public function secretDefaultsToFalse(): void
    {
        $def = new SettingDefinition(key: 'test', type: SettingType::String);

        $this->assertFalse($def->isSecret());
    }

    #[Test]
    public function secretFlagEnabled(): void
    {
        $def = new SettingDefinition(key: 'billing.stripe_key', type: SettingType::String, secret: true);

        $this->assertTrue($def->isSecret());
    }

    #[Test]
    public function throwsOnSecretWithNonStringType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Secret flag is only supported for string type settings');

        new SettingDefinition(key: 'test', type: SettingType::Int, secret: true);
    }

    /**
     * @return array<string, array{SettingType}>
     */
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
    #[Test]
    public function throwsOnSecretWithAnyNonStringType(SettingType $type): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SettingDefinition(key: 'test', type: $type, secret: true);
    }

    #[Test]
    public function fromConfigWithSecretFlag(): void
    {
        $def = SettingDefinition::fromConfig('billing.stripe_key', [
            'type' => 'string',
            'secret' => true,
        ]);

        $this->assertTrue($def->isSecret());
        $this->assertSame(SettingType::String, $def->type);
    }

    #[Test]
    public function fromConfigWithoutSecretFlag(): void
    {
        $def = SettingDefinition::fromConfig('mail.from', [
            'type' => 'string',
            'default' => 'noreply@example.com',
        ]);

        $this->assertFalse($def->isSecret());
    }
}
