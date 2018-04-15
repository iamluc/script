<?php

namespace Iamluc\Script\Lib;

use Iamluc\Script\Output;
use Iamluc\Script\Scope;

interface LibInterface
{
    public function register(Scope $scope, Output $output);
}
