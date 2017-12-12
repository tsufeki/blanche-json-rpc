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

    public function getMethodForRequest(string $methodName): callable
    {
        if (!isset($this->requests[$methodName])) {
            throw new MethodNotFoundException();
        }

        return $this->requests[$methodName];
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
}
