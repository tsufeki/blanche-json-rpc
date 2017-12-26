<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Dispatcher;

use PHPUnit\Framework\TestCase;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodProvider;
use Tsufeki\BlancheJsonRpc\Dispatcher\SimpleMethodRegistry;
use Tsufeki\BlancheJsonRpc\Exception\MethodNotFoundException;

/**
 * @covers \Tsufeki\BlancheJsonRpc\Dispatcher\SimpleMethodRegistry
 */
class SimpleMethodRegistryTest extends TestCase
{
    public function test_returns_request()
    {
        $callable = function () {};
        $registry = new SimpleMethodRegistry();

        $registry->setMethodForRequest('foo', $callable);

        $this->assertSame($callable, $registry->getMethodForRequest('foo'));
    }

    public function test_returns_default_request()
    {
        $callable = function () {};
        $registry = new SimpleMethodRegistry();

        $registry->setDefaultRequestMethod($callable);

        $this->assertSame($callable, $registry->getMethodForRequest('foo'));
    }

    public function test_throws_request_on_unknown_request()
    {
        $registry = new SimpleMethodRegistry();

        $this->expectException(MethodNotFoundException::class);
        $registry->getMethodForRequest('foo');
    }

    public function test_returns_notifications()
    {
        $callables = [function () {}, function (int $x) {}];
        $registry = new SimpleMethodRegistry();

        $registry->addMethodForNotification('foo', $callables[0]);
        $registry->addMethodForNotification('foo', $callables[1]);

        $this->assertSame($callables, $registry->getMethodsForNotification('foo'));
    }

    public function test_returns_empty_array_when_no_notifications_registered()
    {
        $registry = new SimpleMethodRegistry();

        $this->assertSame([], $registry->getMethodsForNotification('foo'));
    }

    public function test_adds_from_provider()
    {
        $provider = new class() implements MethodProvider {
            public function getRequests(): array
            {
                return ['foo' => 'fooFoo'];
            }

            public function getNotifications(): array
            {
                return ['bar' => 'barBar', 'baz' => ['bazBaz']];
            }

            public function fooFoo()
            {
            }

            public function barBar()
            {
            }

            public function bazBaz()
            {
            }
        };

        $registry = new SimpleMethodRegistry();
        $registry->addProvider($provider);

        $this->assertSame([$provider, 'fooFoo'], $registry->getMethodForRequest('foo'));
        $this->assertSame([[$provider, 'barBar']], $registry->getMethodsForNotification('bar'));
        $this->assertSame([[$provider, 'bazBaz']], $registry->getMethodsForNotification('baz'));
    }
}
