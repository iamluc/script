Script
======

`Script` is a PHP library for parsing and executing Lua-like scripts.

Example
-------

```php
<?php

$parser = new \Iamluc\Script\Parser();
$sandbox = new \Iamluc\Script\Sandbox();

$script = <<<EOS
print([[Hello "cruel" world !!]])
EOS;

$sandbox->eval($parser->parse($script));
echo $sandbox->getOutput();
```

License
-------

[MIT](https://opensource.org/licenses/MIT)
