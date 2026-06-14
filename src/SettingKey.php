<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

use Rasuvaeff\Yii3Settings\Exception\InvalidSettingKeyException;

/**
 * @api
 */
final readonly class SettingKey implements \Stringable
{
    private const string KEY_PATTERN = '/^[a-z][a-z0-9_.-]*$/';

    public function __construct(
        public string $value,
    ) {
        if (!preg_match(self::KEY_PATTERN, $this->value)) {
            throw new InvalidSettingKeyException(
                message: sprintf('Invalid setting key "%s"', $this->value),
            );
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
