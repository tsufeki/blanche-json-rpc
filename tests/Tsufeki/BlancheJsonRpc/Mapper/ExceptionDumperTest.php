<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Mapper;

use PHPUnit\Framework\TestCase;
use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\BlancheJsonRpc\Mapper\ExceptionDumper;
use Tsufeki\KayoJsonMapper\Context\Context;
use Tsufeki\KayoJsonMapper\Exception\UnsupportedTypeException;

/**
 * @covers \Tsufeki\BlancheJsonRpc\Mapper\ExceptionDumper
 */
class ExceptionDumperTest extends TestCase
{
    public function test_dumps_exception()
    {
        $dumper = new ExceptionDumper();
        $exception = new JsonRpcException('foo', 42, [1, 2]);

        $dumped = $dumper->dump($exception, new Context());

        $this->assertInstanceOf(\stdClass::class, $dumped);
        $this->assertSame([
            'code' => 42,
            'message' => 'foo',
            'data' => [1, 2],
        ], (array)$dumped);
    }

    public function test_does_not_dump_null_data()
    {
        $dumper = new ExceptionDumper();
        $exception = new JsonRpcException('foo', 42);

        $dumped = $dumper->dump($exception, new Context());

        $this->assertFalse(isset($dumped->data));
    }

    /**
     * @dataProvider unsupported_data
     */
    public function test_support_only_json_rpc_exception($value)
    {
        $dumper = new ExceptionDumper();

        $this->expectException(UnsupportedTypeException::class);
        $dumper->dump($value, new Context());
    }

    public function unsupported_data(): array
    {
        return [
            [new \Exception()],
            [42],
            [[]],
        ];
    }
}
