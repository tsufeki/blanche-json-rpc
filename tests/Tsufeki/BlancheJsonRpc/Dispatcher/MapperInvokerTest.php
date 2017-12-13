<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Dispatcher;

use PHPUnit\Framework\TestCase;
use Recoil\React\ReactKernel;
use Tsufeki\BlancheJsonRpc\Dispatcher\MapperInvoker;
use Tsufeki\BlancheJsonRpc\Exception\InvalidParamsException;
use Tsufeki\KayoJsonMapper\Exception\MapperException;
use Tsufeki\KayoJsonMapper\Mapper;

/**
 * @covers \Tsufeki\BlancheJsonRpc\Dispatcher\MapperInvoker
 */
class MapperInvokerTest extends TestCase
{
    /**
     * @dataProvider callable_data
     */
    public function test_invokes($callable)
    {
        $args = ['x' => 'foo'];
        $mappedArgs = ['foo'];
        $returned = 'foobar';
        $dumped = 'foobarbaz';

        $mapper = $this->createMock(Mapper::class);
        $mapper
            ->expects($this->once())
            ->method('loadArguments')
            ->with($this->identicalTo($args))
            ->willReturn($mappedArgs);
        $mapper
            ->expects($this->once())
            ->method('dump')
            ->with($this->identicalTo($returned))
            ->willReturn($dumped);

        $invoker = new MapperInvoker($mapper);

        /** @var mixed $result */
        $result = null;
        ReactKernel::start(function () use ($invoker, $callable, $args, &$result) {
            $result = yield $invoker->invoke($callable, $args);
        });

        $this->assertSame($dumped, $result);
    }

    public function callable_data(): array
    {
        return [
            [function ($x) {
                return $x . 'bar';
            }],
            [function ($x) {
                yield;

                return $x . 'bar';
            }],
        ];
    }

    public function test_throws_on_mapping_error()
    {
        $mapper = $this->createMock(Mapper::class);
        $mapper
            ->expects($this->once())
            ->method('loadArguments')
            ->with($this->identicalTo([]))
            ->willThrowException(new MapperException());

        $invoker = new MapperInvoker($mapper);

        $this->expectException(InvalidParamsException::class);
        ReactKernel::start(function () use ($invoker) {
            yield $invoker->invoke(function () {}, []);
        });
    }
}
