<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc;

use PHPUnit\Framework\Constraint\JsonMatches;
use PHPUnit\Framework\TestCase;
use Recoil\React\ReactKernel;
use Tsufeki\BlancheJsonRpc\Dispatcher\Dispatcher;
use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\BlancheJsonRpc\JsonRpc;
use Tsufeki\BlancheJsonRpc\Mapper\MapperFactory;
use Tsufeki\BlancheJsonRpc\Transport\Transport;

/**
 * @covers \Tsufeki\BlancheJsonRpc\JsonRpc
 * @covers \Tsufeki\BlancheJsonRpc\Mapper\MapperFactory
 */
class JsonRpcTest extends TestCase
{
    private function getJsonRpc(Transport $transport, Dispatcher $dispatcher): JsonRpc
    {
        return new JsonRpc(
            $transport,
            $dispatcher,
            (new MapperFactory())->create(),
            (function (): \Generator {
                for ($i = 1;; $i++) {
                    yield $i;
                }
            })()
        );
    }

    public function test_sends_notification()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->once())
                ->method('send')
                ->with(new JsonMatches('{"jsonrpc": "2.0", "method": "foo", "params": [1, 2]}'))
                ->willReturn((function () { yield; })());

            $dispatcher = $this->createMock(Dispatcher::class);
            $rpc = $this->getJsonRpc($transport, $dispatcher);

            yield $rpc->notify('foo', [1, 2]);
        });
    }

    public function test_sends_request_and_returns_result()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->once())
                ->method('send')
                ->with(new JsonMatches('{"jsonrpc": "2.0", "id": 1, "method": "foo", "params": [1, 2]}'))
                ->willReturn((function () use (&$rpc) {
                    yield $rpc->receive('{"jsonrpc": "2.0", "id": 1, "result": 42}');
                })());

            $dispatcher = $this->createMock(Dispatcher::class);
            $rpc = $this->getJsonRpc($transport, $dispatcher);

            $result = yield $rpc->call('foo', [1, 2]);

            $this->assertSame(42, $result);
        });
    }

    public function test_sends_request_and_throws()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->once())
                ->method('send')
                ->with(new JsonMatches('{"jsonrpc": "2.0", "id": 1, "method": "foo", "params": [1, 2]}'))
                ->willReturn((function () use (&$rpc) {
                    yield $rpc->receive('{"jsonrpc": "2.0", "id": 1, "error": {"code": 84, "message": "bar"}}');
                })());

            $dispatcher = $this->createMock(Dispatcher::class);
            $rpc = $this->getJsonRpc($transport, $dispatcher);

            $this->expectException(JsonRpcException::class);
            yield $rpc->call('foo', [1, 2]);
        });
    }

    public function test_receives_notification()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->never())
                ->method('send');

            $dispatcher = $this->createMock(Dispatcher::class);
            $dispatcher
                ->expects($this->once())
                ->method('dispatchNotification')
                ->with($this->identicalTo('fooBar'), $this->identicalTo([1, 2]));

            $rpc = $this->getJsonRpc($transport, $dispatcher);

            yield $rpc->receive('{"jsonrpc": "2.0", "method": "fooBar", "params": [1, 2]}');
        });
    }

    public function test_ignores_notification_error()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->never())
                ->method('send');

            $dispatcher = $this->createMock(Dispatcher::class);
            $dispatcher
                ->expects($this->once())
                ->method('dispatchNotification')
                ->willThrowException(new \Exception());

            $rpc = $this->getJsonRpc($transport, $dispatcher);

            yield $rpc->receive('{"jsonrpc": "2.0", "method": "fooBar", "params": [1, 2]}');
            $this->assertTrue(true);
        });
    }

    public function test_receives_request()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->once())
                ->method('send')
                ->with(new JsonMatches('{"jsonrpc": "2.0", "id": 7, "result": 42}'))
                ->willReturn((function () { yield; })());

            $dispatcher = $this->createMock(Dispatcher::class);
            $dispatcher
                ->expects($this->once())
                ->method('dispatchRequest')
                ->with($this->identicalTo('fooBar'), $this->identicalTo([1, 2]))
                ->willReturn((function () {
                    yield;

                    return 42;
                })());

            $rpc = $this->getJsonRpc($transport, $dispatcher);

            yield $rpc->receive('{"jsonrpc": "2.0", "id": 7, "method": "fooBar", "params": [1, 2]}');
        });
    }

    public function test_responds_with_error_on_invalid_json()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->once())
                ->method('send')
                ->with(new JsonMatches('{"jsonrpc": "2.0", "id": null, "error": {"code": -32700, "message": "Parse error"}}'))
                ->willReturn((function () { yield; })());

            $dispatcher = $this->createMock(Dispatcher::class);
            $rpc = $this->getJsonRpc($transport, $dispatcher);

            yield $rpc->receive('{"qaz');
        });
    }

    public function test_receives_batch()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->once())
                ->method('send')
                ->with(new JsonMatches('[{"jsonrpc": "2.0", "id": 7, "result": 42}]'))
                ->willReturn((function () { yield; })());

            $dispatcher = $this->createMock(Dispatcher::class);
            $dispatcher
                ->expects($this->once())
                ->method('dispatchRequest')
                ->with($this->identicalTo('foo'), $this->identicalTo([1, 2]))
                ->willReturn((function () {
                    yield;

                    return 42;
                })());
            $dispatcher
                ->expects($this->once())
                ->method('dispatchNotification')
                ->with($this->identicalTo('bar'), $this->identicalTo([true]))
                ->willReturn((function () { yield; })());

            $rpc = $this->getJsonRpc($transport, $dispatcher);

            yield $rpc->receive('[
                {"jsonrpc": "2.0", "id": 7, "method": "foo", "params": [1, 2]},
                {"jsonrpc": "2.0", "method": "bar", "params": [true]}
            ]');
        });
    }

    public function test_responds_with_error_on_dispatch_exception()
    {
        ReactKernel::start(function () {
            $transport = $this->createMock(Transport::class);
            $transport
                ->expects($this->once())
                ->method('send')
                ->with(new JsonMatches('{"jsonrpc": "2.0", "id": 7, "error": {"code": -32000, "message": "Server error"}}'))
                ->willReturn((function () { yield; })());

            $dispatcher = $this->createMock(Dispatcher::class);
            $dispatcher
                ->expects($this->once())
                ->method('dispatchRequest')
                ->with($this->identicalTo('fooBar'), $this->identicalTo([1, 2]))
                ->willReturn((function () {
                    yield;

                    throw new \Exception();
                })());

            $rpc = $this->getJsonRpc($transport, $dispatcher);

            yield $rpc->receive('{"jsonrpc": "2.0", "id": 7, "method": "fooBar", "params": [1, 2]}');
        });
    }
}
