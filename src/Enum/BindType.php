<?php

namespace DatabaseManager\Enum;

/**
 * Bond types
 */
enum BindType : string {

    case innerJoin = 'INNER JOIN';
    case leftJoin = 'LEFT JOIN';
    case rightJoin = 'RIGHT JOIN';
    case crossJoin = 'CROSS JOIN';

}