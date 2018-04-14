<?php

namespace Iamluc\Script\Exception;

class GotoException extends CodeFlowException
{
    private $target;

    public function __construct($target)
    {
        $this->target = $target;
    }

    public function getTarget()
    {
        return $this->target;
    }
}
