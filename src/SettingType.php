<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Settings;

/**
 * @api
 */
enum SettingType: string
{
    case String = 'string';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Array = 'array';
}
