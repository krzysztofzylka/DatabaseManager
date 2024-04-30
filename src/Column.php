<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\ColumnType;
use krzysztofzylka\DatabaseManager\Enum\Trigger;

class Column
{

    private string $name;

    private ColumnType $type;

    private null|string|int $typeSize = null;

    private bool $autoincrement = false;

    private bool $primary = false;

    private ?bool $null = null;

    private mixed $default = null;

    private bool $defaultDefined = false;

    private ?string $extra = null;

    private bool $unsigned = false;

    private array $triggers = [];

    /**
     * Creates a new instance of the class.
     * @param string|null $name The name of the column (optional)
     * @param ColumnType $columnType The type of the column (default: ColumnType::varchar)
     * @param mixed $size The size of the column (default: 255)
     * @return self The new instance of the class
     */
    public static function create(?string $name = null, ColumnType $columnType = ColumnType::varchar, mixed $size = 255): self
    {
        return new self($name, $columnType, $size);
    }

    /**
     * Constructor
     * @param ?string $name
     * @param ColumnType $columnType
     * @param mixed $size
     */
    public function __construct(?string $name = null, ColumnType $columnType = ColumnType::varchar, mixed $size = 255)
    {
        if (!is_null($name)) {
            $this->setName($name)->setType($columnType, $size);
        }
    }

    /**
     * Set name
     * @param string $name
     * @return Column
     */
    public function setName(string $name): Column
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set autoincrement
     * @param bool $autoincrement
     * @return Column
     */
    public function setAutoincrement(bool $autoincrement): Column
    {
        $this->autoincrement = $autoincrement;

        return $this;
    }

    /**
     * @param bool $primary
     * @return Column
     */
    public function setPrimary(bool $primary): Column
    {
        $this->primary = $primary;

        return $this;
    }

    /**
     * Set null
     * @param bool $null
     * @return Column
     */
    public function setNull(bool $null): Column
    {
        $this->null = $null;

        return $this;
    }

    /**
     * Set default data
     * @param mixed $default
     * @return Column
     */
    public function setDefault(mixed $default): Column
    {
        $this->default = $default;
        $this->defaultDefined = true;

        return $this;
    }

    /**
     * Set extra data
     * @param ?string $extra
     * @return Column
     */
    public function setExtra(?string $extra): Column
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Set type
     * @param ColumnType $type
     * @param mixed $size (array for enum)
     * @return Column
     */
    public function setType(ColumnType $type, mixed $size = null): self
    {
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Is autoincrement
     * @return bool
     */
    public function isAutoincrement(): bool
    {
        return $this->autoincrement;
    }

    /**
     * Is primary
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->primary;
    }

    /**
     * Is null
     * @return ?bool
     */
    public function isNull(): ?bool
    {
        return $this->null;
    }

    /**
     * Get default data
     * @return int|string|null
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Get extra
     * @return ?string
     */
    public function getExtra(): ?string
    {
        return $this->extra;
    }

    /**
     * Get type
     * @return ColumnType
     */
    public function getType(): ColumnType
    {
        return $this->type;
    }

    /**
     * Get type size
     * @return int|string|null
     */
    public function getTypeSize(): int|string|null
    {
        return $this->typeSize;
    }

    /**
     * Is default defined
     * @return bool
     */
    public function isDefaultDefined(): bool
    {
        return $this->defaultDefined;
    }

    /**
     * Is unsigned
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * Set unsigned
     * @param bool $unsigned
     * @return Column
     */
    public function setUnsigned(bool $unsigned): Column
    {
        $this->unsigned = $unsigned;

        return $this;
    }

    /**
     * Add trigger to column
     * @param Trigger $trigger
     * @return $this
     */
    public function addTrigger(Trigger $trigger): Column
    {
        $this->triggers[] = $trigger;

        return $this;
    }

    /**
     * Get triggers
     * @return array
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

}