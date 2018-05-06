<?php

namespace Iamluc\Script\Extension;

interface ExtensionInterface
{
    public function dispatch($eventName, $args);
}
