<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

interface Dispatcher
{
    public function dispatch(string $methodName, $args): \Generator;
}
