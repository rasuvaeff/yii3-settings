<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests\Crypto;

use Rasuvaeff\Yii3Settings\Crypto\UnknownEncryptionKeyException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(UnknownEncryptionKeyException::class)]
final class UnknownEncryptionKeyExceptionTest
{
    public function carriesTheGivenMessage(): void
    {
        $exception = new UnknownEncryptionKeyException('Key ID "k2" not found in KeyRing');

        Assert::same($exception->getMessage(), 'Key ID "k2" not found in KeyRing');
    }

    public function isARuntimeException(): void
    {
        Assert::instanceOf(new UnknownEncryptionKeyException(), \RuntimeException::class);
    }
}
