<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
interface SettingsInspector
{
    public function describe(string $key): SettingState;

    /**
     * @return list<SettingState>
     */
    public function describeAll(): array;
}
