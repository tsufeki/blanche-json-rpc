<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Mapper;

use phpDocumentor\Reflection\TypeResolver;
use PHPUnit\Framework\TestCase;
use Tsufeki\BlancheJsonRpc\Exception\InvalidParamsException;
use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\BlancheJsonRpc\Mapper\ExceptionLoader;
use Tsufeki\KayoJsonMapper\Context\Context;
use Tsufeki\KayoJsonMapper\Exception\TypeMismatchException;
use Tsufeki\KayoJsonMapper\Exception\UnsupportedTypeException;

/**
 * @covers \Tsufeki\BlancheJsonRpc\Mapper\ExceptionLoader
 */
class ExceptionLoaderTest extends TestCase
{
    /**
     * @dataProvider exception_data
     */
    public function test_load_exception($code, $expectedClass)
    {
        $loader = new ExceptionLoader();
        $resolver = new TypeResolver();

        $data = new \stdClass();
        $data->message = 'foo';
        $data->code = $code;
        $data->data = [1, 2];

        $exception = $loader->load($data, $resolver->resolve('\\' . JsonRpcException::class), new Context());

        $this->assertInstanceOf($expectedClass, $exception);
        $this->assertSame('foo', $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame([1, 2], $exception->getData());
    }

    public function exception_data(): array
    {
        return [
            [InvalidParamsException::CODE_MAX, InvalidParamsException::class],
            [42, JsonRpcException::class],
        ];
    }

    public function test_throws_on_non_stdclass_data()
    {
        $loader = new ExceptionLoader();
        $resolver = new TypeResolver();

        $this->expectException(TypeMismatchException::class);
        $loader->load(42, $resolver->resolve('\\' . JsonRpcException::class), new Context());
    }

    /**
     * @dataProvider bad_data
     */
    public function test_throws_on_bad_data($message, $code)
    {
        $loader = new ExceptionLoader();
        $resolver = new TypeResolver();

        $data = new \stdClass();
        $data->message = $message;
        $data->code = $code;

        $this->expectException(TypeMismatchException::class);
        $loader->load($data, $resolver->resolve('\\' . JsonRpcException::class), new Context());
    }

    public function bad_data(): array
    {
        return [
            [['foo'], 42],
            ['foo', false],
        ];
    }

    /**
     * @dataProvider unsupported_data
     */
    public function test_support_only_json_rpc_exception($type)
    {
        $loader = new ExceptionLoader();
        $resolver = new TypeResolver();

        $this->expectException(UnsupportedTypeException::class);
        $loader->load(new \stdClass(), $resolver->resolve($type), new Context());
    }

    public function unsupported_data(): array
    {
        return [
            ['\\Exception'],
            ['int'],
        ];
    }
}
