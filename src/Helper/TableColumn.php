<?php

namespace DatabaseManager\Helper;

use DatabaseManager\Enum\ColumnType;

class TableColumn {

    private string $name;
    private ColumnType $type;
    private null|string|int $typeSize = null;
    private bool $autoincrement = false;
    private bool $primary = false;
    private bool $null = true;
    private mixed $default = null;
    private bool $defaultDefined = false;
    private ?string $extra = null;

    /**
     * Set name
     * @param string $name
     * @return TableColumn
     */
    public function setName(string $name) : TableColumn {
        $this->name = $name;

        return $this;
    }

    /**
     * Set autoincrement
     * @param bool $autoincrement
     * @return TableColumn
     */
    public function setAutoincrement(bool $autoincrement) : TableColumn {
        $this->autoincrement = $autoincrement;

        return $this;
    }

    /**
     * @param bool $primary
     * @return TableColumn
     */
    public function setPrimary(bool $primary) : TableColumn {
        $this->primary = $primary;

        return $this;
    }

    /**
     * Set null
     * @param bool $null
     * @return TableColumn
     */
    public function setNull(bool $null) : TableColumn {
        $this->null = $null;

        return $this;
    }

    /**
     * Set default data
     * @param mixed $default
     * @return TableColumn
     */
    public function setDefault(mixed $default) : TableColumn {
        $this->default = $default;
        $this->defaultDefined = true;

        return $this;
    }

    /**
     * Set extra data
     * @param ?string $extra
     * @return TableColumn
     */
    public function setExtra(?string $extra) : TableColumn {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Set type
     * @param ColumnType $type
     * @param string|int|null $size
     * @return TableColumn
     */
    public function setType(ColumnType $type, null|string|int $size = null) : self {
        $this->type = $type;
        $this->typeSize = $size;

        return $this;
    }

    /**
     * get name
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * Is autoincrement
     * @return bool
     */
    public function isAutoincrement() : bool {
        return $this->autoincrement;
    }

    /**
     * Is primary
     * @return bool
     */
    public function isPrimary() : bool {
        return $this->primary;
    }

    /**
     * Is null
     * @return bool
     */
    public function isNull() : bool {
        return $this->null;
    }

    /**
     * Get default data
     * @return int|string|null
     */
    public function getDefault(): mixed {
        return $this->default;
    }

    /**
     * Get extra
     * @return ?string
     */
    public function getExtra() : ?string {
        return $this->extra;
    }

    /**
     * Get type
     * @return ColumnType
     */
    public function getType() : ColumnType {
        return $this->type;
    }

    /**
     * Get type size
     * @return int|string|null
     */
    public function getTypeSize() : int|string|null {
        return $this->typeSize;
    }

    /**
     * Is default defined
     * @return bool
     */
    public function isDefaultDefined(): bool {
        return $this->defaultDefined;
    }

}