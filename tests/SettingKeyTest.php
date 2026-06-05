<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\Exception\InvalidSettingKeyException;
use Rasuvaeff\Yii3Settings\SettingKey;

#[CoversClass(SettingKey::class)]
final class SettingKeyTest extends TestCase
{
    #[Test]
    public function acceptsValidKey(): void
    {
        $key = new SettingKey('mail.from');

        $this->assertSame('mail.from', $key->toString());
        $this->assertSame('mail.from', (string) $key);
    }

    #[Test]
    public function rejectsInvalidKey(): void
    {
        $this->expectException(InvalidSettingKeyException::class);

        new SettingKey('Invalid Key');
    }
}
