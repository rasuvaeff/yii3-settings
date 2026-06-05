<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
interface SettingsProvider
{
    public function has(string $key): bool;

    public function get(string $key): mixed;
}
