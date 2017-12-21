<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Dispatcher;

use PHPUnit\Framework\TestCase;
use Recoil\React\ReactKernel;
use Tsufeki\BlancheJsonRpc\Dispatcher\RawInvoker;

/**
 * @covers \Tsufeki\BlancheJsonRpc\Dispatcher\RawInvoker
 */
class RawInvokerTest extends TestCase
{
    /**
     * @dataProvider callable_data
     */
    public function test_invokes($callable)
    {
        $args = ['x' => 'foo'];
        $expected = 'foobar';

        $invoker = new RawInvoker();

        /** @var mixed $result */
        $result = null;
        ReactKernel::start(function () use ($invoker, $callable, $args, &$result) {
            $result = yield $invoker->invoke($callable, $args);
        });

        $this->assertSame($expected, $result);
    }

    public function callable_data(): array
    {
        return [
            [function ($x) {
                return $x['x'] . 'bar';
            }],
            [function ($x) {
                yield;

                return $x['x'] . 'bar';
            }],
        ];
    }
}
