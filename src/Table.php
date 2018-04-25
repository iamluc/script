<?php

namespace Iamluc\Script;

class Table
{
    private $index = 0;
    private $map;
    private $objectMap;

    public function __construct(array $map = [])
    {
        $this->map = $map;
        $this->objectMap = new \SplObjectStorage();
    }

    public function all(): iterable
    {
        foreach ($this->map as $index => $value) {
            if (null === $value) {
                continue;
            }

            yield $index => $value;
        }

        foreach ($this->objectMap as $index) {
            $value = $this->objectMap[$index];
            if (null === $value) {
                continue;
            }

            yield $index => $value;
        }
    }

    public function has($index): bool
    {
        if (is_object($index)) {
            return array_key_exists($index, $this->objectMap);
        }

        return array_key_exists($index, $this->map);
    }

    public function get($index)
    {
        if (is_object($index)) {
            return $this->objectMap[$index] ?? null;
        }

        return $this->map[$index] ?? null;
    }

    public function add($value, $index = null): void
    {
        if (is_object($index)) {
            $this->objectMap[$index] = $value;

            return;
        }

        $index = $index ?? ++$this->index;

        $this->map[$index] = $value;
    }

    public function sort(callable $sorter): void
    {
        usort($this->map, $sorter);
    }

    public function toArray(): array
    {
        $res = [];
        foreach ($this->all() as $index => $value) {
            if ($value instanceof self) {
                $value = $value->toArray();
            }

            $res[$index] = $value;
        }

        return $res;
    }
}
