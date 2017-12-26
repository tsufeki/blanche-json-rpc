<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

use Tsufeki\BlancheJsonRpc\Exception\MethodNotFoundException;

class SimpleMethodRegistry implements MethodRegistry
{
    /**
     * @var callable[]
     */
    private $requests = [];

    /**
     * @var callable[][]
     */
    private $notifications = [];

    /**
     * @var callable|null
     */
    private $defaultRequest = null;

    /**
     * @param string   $methodName
     * @param callable $callable   Plain callable or async generator.
     *
     * @return $this
     */
    public function setMethodForRequest(string $methodName, callable $callable): self
    {
        $this->requests[$methodName] = $callable;

        return $this;
    }

    /**
     * @param callable|null $callable
     *
     * @return $this
     */
    public function setDefaultRequestMethod(callable $callable = null): self
    {
        $this->defaultRequest = $callable;

        return $this;
    }

    public function getMethodForRequest(string $methodName): callable
    {
        $method = $this->requests[$methodName] ?? $this->defaultRequest;
        if ($method === null) {
            throw new MethodNotFoundException();
        }

        return $method;
    }

    /**
     * @param string   $methodName
     * @param callable $callable   Plain callable or async generator.
     *
     * @return $this
     */
    public function addMethodForNotification(string $methodName, callable $callable): self
    {
        $this->notifications[$methodName][] = $callable;

        return $this;
    }

    public function getMethodsForNotification(string $methodName): array
    {
        return $this->notifications[$methodName] ?? [];
    }

    public function addProvider(MethodProvider $provider): self
    {
        foreach ($provider->getRequests() as $rpcMethod => $phpMethod) {
            $this->setMethodForRequest($rpcMethod, [$provider, $phpMethod]);
        }

        foreach ($provider->getNotifications() as $rpcMethod => $phpMethods) {
            $phpMethods = is_array($phpMethods) ? $phpMethods : [$phpMethods];
            foreach ($phpMethods as $phpMethod) {
                $this->addMethodForNotification($rpcMethod, [$provider, $phpMethod]);
            }
        }

        return $this;
    }
}
