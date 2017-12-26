<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

interface MethodProvider
{
    /**
     * @return array<string,string> JSON RPC method => PHP method name on this object.
     */
    public function getRequests(): array;

    /**
     * @return array<string,string|string[]> JSON RPC method => PHP method name(s) on this object.
     */
    public function getNotifications(): array;
}
