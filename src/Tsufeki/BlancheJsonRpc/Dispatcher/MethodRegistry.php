<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

use Tsufeki\BlancheJsonRpc\Exception\MethodNotFoundException;

interface MethodRegistry
{
    /**
     * @throws MethodNotFoundException
     */
    public function getMethodForRequest(string $methodName): callable;

    /**
     * @throws MethodNotFoundException
     *
     * @return callable[]
     */
    public function getMethodsForNotification(string $methodName): array;
}
