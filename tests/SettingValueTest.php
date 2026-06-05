<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3Settings\SettingValue;

#[CoversClass(SettingValue::class)]
final class SettingValueTest extends TestCase
{
    #[Test]
    #[DataProvider('normalizationProvider')]
    public function normalizesValueByType(SettingType $type, mixed $value, mixed $expected): void
    {
        $normalized = SettingValue::fromRaw(type: $type, value: $value);

        $this->assertSame($type, $normalized->type);
        $this->assertSame($expected, $normalized->raw());
    }

    /**
     * @return iterable<string, array{SettingType, mixed, mixed}>
     */
    public static function normalizationProvider(): iterable
    {
        yield 'string kept as string' => [SettingType::String, 'USD', 'USD'];
        yield 'string cast from int' => [SettingType::String, 42, '42'];
        yield 'int kept as int' => [SettingType::Int, 42, 42];
        yield 'int cast from string' => [SettingType::Int, '42', 42];
        yield 'float kept as float' => [SettingType::Float, 1.5, 1.5];
        yield 'float cast from string' => [SettingType::Float, '1.5', 1.5];
        yield 'bool kept as bool' => [SettingType::Bool, true, true];
        yield 'bool cast from int' => [SettingType::Bool, 1, true];
        yield 'array kept as array' => [SettingType::Array, ['a', 'b'], ['a', 'b']];
        yield 'array cast from scalar' => [SettingType::Array, 'x', ['x']];
    }
}
