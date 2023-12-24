<?php

namespace krzysztofzylka\DatabaseManager\Enum;

use ValueError;

/**
 * Bond types
 */
enum BindType: string
{

    case innerJoin = 'INNER JOIN';
    case leftJoin = 'LEFT JOIN';
    case rightJoin = 'RIGHT JOIN';
    case crossJoin = 'CROSS JOIN';
    case fullJoin = 'FULL OUTER JOIN';
    case hasOne = '#HAS_ONE#';
    case hasMany = '#HAS_MANY#';

    /**
     * Get bind from name
     * @param string $name
     * @return BindType
     */
    public static function getFromName(string $name): BindType
    {
        foreach (self::cases() as $status) {
            if ($name === $status->name) {
                return constant("self::$status->name");
            }
        }
        throw new ValueError();
    }

}