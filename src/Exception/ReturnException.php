<?php

namespace Iamluc\Script\Exception;

use Iamluc\Script\ReturnSet;

class ReturnException extends CodeFlowException
{
    private $returnSet;

    public function __construct(ReturnSet $returnSet)
    {
        $this->returnSet = $returnSet;
    }

    public function getReturnSet()
    {
        return $this->returnSet;
    }
}
