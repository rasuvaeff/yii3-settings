<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings\Tests\Exception;

use Rasuvaeff\Yii3Settings\Exception\ReadonlySettingException;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ReadonlySettingException::class)]
final class ReadonlySettingExceptionTest
{
    public function carriesTheGivenMessage(): void
    {
        $exception = new ReadonlySettingException('Setting "billing.rate" is readonly');

        Assert::same($exception->getMessage(), 'Setting "billing.rate" is readonly');
    }

    public function isARuntimeException(): void
    {
        Assert::instanceOf(new ReadonlySettingException(), \RuntimeException::class);
    }
}
