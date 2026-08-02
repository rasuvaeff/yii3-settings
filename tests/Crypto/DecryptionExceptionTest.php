<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests\Crypto;

use Rasuvaeff\Yii3Settings\Crypto\DecryptionException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(DecryptionException::class)]
final class DecryptionExceptionTest
{
    public function carriesTheGivenMessage(): void
    {
        $exception = new DecryptionException('AEAD tag mismatch');

        Assert::same($exception->getMessage(), 'AEAD tag mismatch');
    }

    public function isARuntimeException(): void
    {
        Assert::instanceOf(new DecryptionException(), \RuntimeException::class);
    }
}
