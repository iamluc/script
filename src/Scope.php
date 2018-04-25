<?php

namespace Iamluc\Script;

class Scope extends Table
{
    public function setMulti(array $variables): void
    {
        foreach ($variables as $name => $value) {
            $this->add($value, $name);
        }
    }
}
