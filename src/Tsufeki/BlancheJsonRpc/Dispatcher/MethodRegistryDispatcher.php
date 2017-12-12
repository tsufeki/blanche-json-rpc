<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

class MethodRegistryDispatcher implements Dispatcher
{
    /**
     * @var MethodRegistry
     */
    private $methodRegistry;

    /**
     * @var Invoker
     */
    private $invoker;

    public function __construct(MethodRegistry $methodRegistry, Invoker $invoker)
    {
        $this->methodRegistry = $methodRegistry;
        $this->invoker = $invoker;
    }

    public function dispatchRequest(string $methodName, $args): \Generator
    {
        $callable = $this->methodRegistry->getMethodForRequest($methodName);

        return yield $this->invoker->invoke($callable, $args);
    }

    public function dispatchNotification(string $methodName, $args): \Generator
    {
        $callables = $this->methodRegistry->getMethodsForNotification($methodName);

        if (!empty($callables)) {
            yield array_map(function (callable $callable) use ($args) {
                $this->invoker->invoke($callable, $args);
            }, $callables);
        }
    }
}
