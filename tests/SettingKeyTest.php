<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use Rasuvaeff\Yii3Settings\Exception\InvalidSettingKeyException;
use Rasuvaeff\Yii3Settings\SettingKey;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(SettingKey::class)]
final class SettingKeyTest
{
    public function acceptsValidKey(): void
    {
        $key = new SettingKey('mail.from');

        Assert::same($key->toString(), 'mail.from');
        Assert::same((string) $key, 'mail.from');
    }

    public function rejectsInvalidKey(): void
    {
        Expect::exception(InvalidSettingKeyException::class);

        new SettingKey('Invalid Key');
    }
}
