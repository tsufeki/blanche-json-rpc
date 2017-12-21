<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc;

use PHPUnit\Framework\TestCase;
use Recoil\React\ReactKernel;
use Tests\Tsufeki\BlancheJsonRpc\Fixtures\DummyTransportPair;
use Tsufeki\BlancheJsonRpc\Dispatcher\SimpleMethodRegistry;
use Tsufeki\BlancheJsonRpc\JsonRpc;
use Tsufeki\BlancheJsonRpc\MappedJsonRpc;

/**
 * @covers \Tsufeki\BlancheJsonRpc\JsonRpc::create
 * @covers \Tsufeki\BlancheJsonRpc\MappedJsonRpc
 */
class FunctionalTest extends TestCase
{
    public function test_mapped_rpc_call()
    {
        ReactKernel::start(function () {
            list($clientTransport, $serverTransport) = DummyTransportPair::create();

            $client = MappedJsonRpc::create($clientTransport, new SimpleMethodRegistry());

            $methodRegistry = new SimpleMethodRegistry();
            $methodRegistry->setMethodForRequest('addOneDay', function (\DateTime $d) {
                $d->modify('+1 day');

                return $d;
            });
            $server = MappedJsonRpc::create($serverTransport, $methodRegistry);

            $result = yield $client->call('addOneDay', [new \DateTime('2017-12-21')], \DateTime::class);

            $this->assertEquals(new \DateTime('2017-12-22'), $result);
        });
    }

    public function test_mapped_rpc_notify()
    {
        $callCounter = 0;

        $kernel = ReactKernel::create();
        $kernel->execute(function () use (&$callCounter) {
            list($clientTransport, $serverTransport) = DummyTransportPair::create();

            $client = MappedJsonRpc::create($clientTransport, new SimpleMethodRegistry());

            $date = new \DateTime('2017-12-21');
            $method = function (\DateTime $d) use ($date, &$callCounter) {
                $this->assertEquals($date, $d);
                $callCounter++;
            };

            $methodRegistry = new SimpleMethodRegistry();
            $methodRegistry->addMethodForNotification('foo', $method);
            $methodRegistry->addMethodForNotification('foo', $method);
            $server = MappedJsonRpc::create($serverTransport, $methodRegistry);

            yield $client->notify('foo', [$date]);
        });
        $kernel->run();

        $this->assertSame(2, $callCounter);
    }

    public function test_raw_rpc_call()
    {
        ReactKernel::start(function () {
            list($clientTransport, $serverTransport) = DummyTransportPair::create();

            $client = JsonRpc::create($clientTransport, new SimpleMethodRegistry());

            $methodRegistry = new SimpleMethodRegistry();
            $methodRegistry->setMethodForRequest('addOne', function (\stdClass $args) {
                return $args->n + 1;
            });
            $server = JsonRpc::create($serverTransport, $methodRegistry);

            $result = yield $client->call('addOne', ['n' => 3]);

            $this->assertSame(4, $result);
        });
    }
}
