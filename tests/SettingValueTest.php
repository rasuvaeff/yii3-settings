<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3Settings\SettingValue;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(SettingValue::class)]
final class SettingValueTest
{
    #[DataProvider('normalizationProvider')]
    public function normalizesValueByType(SettingType $type, mixed $value, mixed $expected): void
    {
        $normalized = SettingValue::fromRaw(type: $type, value: $value);

        Assert::same($normalized->type, $type);
        Assert::same($normalized->raw(), $expected);
    }

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
