<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

interface Dispatcher
{
    public function dispatchRequest(string $methodName, $args): \Generator;

    public function dispatchNotification(string $methodName, $args): \Generator;
}
