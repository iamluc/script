<?php

namespace Iamluc\Script\Node;

class AssignNode extends Node
{
    private $assignments;
    private $local;

    public function __construct(array $assignments, bool $local)
    {
        $this->assignments = $assignments;
        $this->local = $local;
    }

    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }
}
