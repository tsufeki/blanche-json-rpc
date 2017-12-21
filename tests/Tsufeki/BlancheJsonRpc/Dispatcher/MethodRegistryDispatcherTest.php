<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Dispatcher;

use PHPUnit\Framework\TestCase;
use Recoil\React\ReactKernel;
use Tsufeki\BlancheJsonRpc\Dispatcher\Invoker;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistry;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistryDispatcher;

/**
 * @covers \Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistryDispatcher
 */
class MethodRegistryDispatcherTest extends TestCase
{
    public function test_dispatches_request()
    {
        ReactKernel::start(function () {
            $method = function () {};
            $args = ['n' => 42];
            $return = new \stdClass();

            $methodRegistry = $this->createMock(MethodRegistry::class);
            $methodRegistry
                ->expects($this->once())
                ->method('getMethodForRequest')
                ->with($this->equalTo('foo'))
                ->willReturn($method);

            $invoker = $this->createMock(Invoker::class);
            $invoker
                ->expects($this->once())
                ->method('invoke')
                ->with($this->identicalTo($method), $this->identicalTo($args))
                ->willReturn((function () use ($return) {
                    yield;

                    return $return;
                })());

            $methodRegistryDispatcher = new MethodRegistryDispatcher($methodRegistry, $invoker);

            $this->assertSame($return, yield $methodRegistryDispatcher->dispatchRequest('foo', $args));
        });
    }

    public function test_dispatches_notification()
    {
        ReactKernel::start(function () {
            $method = function () {};
            $args = ['n' => 42];

            $methodRegistry = $this->createMock(MethodRegistry::class);
            $methodRegistry
                ->expects($this->once())
                ->method('getMethodsForNotification')
                ->with($this->equalTo('foo'))
                ->willReturn([$method, $method]);

            $invoker = $this->createMock(Invoker::class);
            $invoker
                ->expects($this->exactly(2))
                ->method('invoke')
                ->with($this->identicalTo($method), $this->identicalTo($args))
                ->willReturnCallback(function () use (&$callCounter) {
                    $callCounter++;
                    yield;
                });

            $callCounter = 0;
            $methodRegistryDispatcher = new MethodRegistryDispatcher($methodRegistry, $invoker);

            yield $methodRegistryDispatcher->dispatchNotification('foo', $args);

            $this->assertSame(2, $callCounter);
        });
    }
}
