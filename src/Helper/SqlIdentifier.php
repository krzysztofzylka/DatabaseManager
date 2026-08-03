<?php

namespace krzysztofzylka\DatabaseManager\Helper;

use InvalidArgumentException;

class SqlIdentifier
{

    /**
     * Allowed shape for a single identifier segment
     */
    private const PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /**
     * Validate a single identifier (table, column or alias name without a dot)
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     */
    public static function validate(string $name): string
    {
        if (!preg_match(self::PATTERN, $name)) {
            throw new InvalidArgumentException('Invalid SQL identifier: "' . $name . '"');
        }

        return $name;
    }

    /**
     * Validate a possibly qualified identifier (e.g. table.column), each segment separately
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     */
    public static function validateQualified(string $name): string
    {
        foreach (explode('.', $name) as $segment) {
            self::validate($segment);
        }

        return $name;
    }

}
