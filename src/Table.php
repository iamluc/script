<?php

namespace Iamluc\Script;

class Table
{
    private $index = 0;
    private $map;

    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    public function get($index)
    {
        return $this->map[$index] ?? null;
    }

    public function add($value, $index = null)
    {
        $index = $index ?? ++$this->index;
        $this->map[$index] = $value;
    }

    public function toArray()
    {
        $res = [];
        foreach ($this->map as $index => $value) {
            if ($value instanceof self) {
                $value = $value->toArray();
            }

            $res[$index] = $value;
        }

        return $res;
    }
}
