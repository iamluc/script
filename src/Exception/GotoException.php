<?php

namespace Iamluc\Script\Exception;

class GotoException extends \Exception
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
