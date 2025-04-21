<?php

namespace Tests\Unit\Helper;

use krzysztofzylka\DatabaseManager\Helper\Table;
use PHPUnit\Framework\TestCase;

class TableHelperTest extends TestCase
{
    public function testPrepareColumnNameWithAliasSimple(): void
    {
        $result = Table::prepareColumnNameWithAlias('column');
        $this->assertEquals('`column`', $result);
    }

    public function testPrepareColumnNameWithAliasTable(): void
    {
        $result = Table::prepareColumnNameWithAlias('table.column');
        $this->assertEquals('`table`.`column`', $result);
    }

    public function testPrepareColumnNameWithAliasSpecialChars(): void
    {
        $result = Table::prepareColumnNameWithAlias('special_table.column_name');
        $this->assertEquals('`special_table`.`column_name`', $result);
    }

    public function testPrepareColumnNameWithAliasNumeric(): void
    {
        $result = Table::prepareColumnNameWithAlias('table1.column2');
        $this->assertEquals('`table1`.`column2`', $result);
    }
}