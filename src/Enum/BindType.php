<?php

namespace DatabaseManager\Enum;

enum BindType : string {

    case innerJoin = 'INNER JOIN';
    case leftJoin = 'LEFT JOIN';
    case rightJoin = 'RIGHT JOIN';
    case crossJoin = 'CROSS JOIN';

}