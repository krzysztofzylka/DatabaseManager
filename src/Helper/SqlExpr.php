<?php

namespace krzysztofzylka\DatabaseManager\Helper;

class SqlExpr
{
    private string $expr;

    private function __construct(string $expr)
    {
        $this->expr = $expr;
    }

    public static function raw(string $expr): self
    {
        return new self($expr);
    }

    public function __toString(): string
    {
        return $this->expr;
    }
}
