<?php

namespace DatabaseManager\Enum;

enum ColumnType {

    //string
    case char;
    case varchar;
    case binary;
    case varbinary;
    case tinyblob;
    case tinytext;
    case text;
    case blob;
    case mediumtext;
    case mediumblob;
    case longtext;
    case longblob;
    case enum;
    case set;

    //numeric
    case bit;
    case tinyint;
    case bool;
    case boolean;
    case smallint;
    case mediumint;
    case int;
    case integer;
    case bigint;
    case float;
    case double;
    case decimal;
    case dec;

    //date and time
    case date;
    case datetime;
    case timestamp;
    case time;
    case year;

    //special
    case json;

}