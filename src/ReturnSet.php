<?php

namespace Iamluc\Script;

class ReturnSet
{
    private $values;

    public function __construct($values = [])
    {
        if (!\is_array($values)) {
            $values = [$values];
        }

        $this->values = $values;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function first()
    {
        return $this->values[0] ?? null;
    }

    public function extra(): array
    {
        if (\count($this->values) <= 1) {
            return [];
        }

        return array_slice($this->values, 1);
    }
}
