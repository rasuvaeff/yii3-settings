<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
interface WritableSettingsProvider extends SettingsProvider
{
    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;
}
