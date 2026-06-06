<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
final readonly class SettingState
{
    public function __construct(
        public string $key,
        public mixed $effectiveValue,
        public bool $hasStoredOverride,
        public string $source,
        public bool $isSecret,
        public bool $isWritable,
    ) {}
}
