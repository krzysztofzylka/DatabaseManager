<?php

namespace DatabaseManager;

use DatabaseManager\Enum\ColumnType;

class Column {

    private string $name;
    private ColumnType $type;
    private null|string|int $typeSize = null;
    private bool $autoincrement = false;
    private bool $primary = false;
    private bool $null = true;
    private mixed $default = null;
    private bool $defaultDefined = false;
    private ?string $extra = null;
    private bool $unsigned = false;

    /**
     * Set name
     * @param string $name
     * @return Column
     */
    public function setName(string $name) : Column {
        $this->name = $name;

        return $this;
    }

    /**
     * Set autoincrement
     * @param bool $autoincrement
     * @return Column
     */
    public function setAutoincrement(bool $autoincrement) : Column {
        $this->autoincrement = $autoincrement;

        return $this;
    }

    /**
     * @param bool $primary
     * @return Column
     */
    public function setPrimary(bool $primary) : Column {
        $this->primary = $primary;

        return $this;
    }

    /**
     * Set null
     * @param bool $null
     * @return Column
     */
    public function setNull(bool $null) : Column {
        $this->null = $null;

        return $this;
    }

    /**
     * Set default data
     * @param mixed $default
     * @return Column
     */
    public function setDefault(mixed $default) : Column {
        $this->default = $default;
        $this->defaultDefined = true;

        return $this;
    }

    /**
     * Set extra data
     * @param ?string $extra
     * @return Column
     */
    public function setExtra(?string $extra) : Column {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Set type
     * @param ColumnType $type
     * @param string|int|null|array $size (array for enum)
     * @return Column
     */
    public function setType(ColumnType $type, null|string|int|array $size = null) : self {
        $this->type = $type;

        if ($type === ColumnType::enum) {
            $this->typeSize = "'" . implode("','", $size) . "'";
        } else {
            $this->typeSize = $size;
        }

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

    /**
     * Is unsigned
     * @return bool
     */
    public function isUnsigned() : bool {
        return $this->unsigned;
    }

    /**
     * Set unsigned
     * @param bool $unsigned
     * @return Column
     */
    public function setUnsigned(bool $unsigned) : self {
        $this->unsigned = $unsigned;

        return $this;
    }

}