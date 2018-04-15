<?php

namespace Iamluc\Script;

class Output
{
    private $output = '';

    public function getOutput(): string
    {
        return $this->output;
    }

    public function write($str)
    {
        $this->output .= $str;
    }

    public function __toString()
    {
        return $this->output;
    }
}
