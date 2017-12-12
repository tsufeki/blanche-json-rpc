<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc;

use Tsufeki\BlancheJsonRpc\Dispatcher\MapperInvoker;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistry;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistryDispatcher;
use Tsufeki\BlancheJsonRpc\Transport\Transport;
use Tsufeki\KayoJsonMapper\Mapper;
use Tsufeki\KayoJsonMapper\MapperBuilder;

class JsonRpc
{
    /**
     * @var Protocol
     */
    private $protocol;

    /**
     * @var Mapper
     */
    private $mapper;

    public function __construct(Protocol $protocol, Mapper $mapper)
    {
        $this->protocol = $protocol;
        $this->mapper = $mapper;
    }

    /**
     * @param string          $method
     * @param array|\stdClass $args
     * @param string          $returnType
     *
     * @resolve mixed
     */
    public function call(string $method, $args = [], string $returnType = 'mixed'): \Generator
    {
        $result = yield $this->protocol->call($method, $args);

        return $this->mapper->load($result, $returnType);
    }

    /**
     * @param string          $method
     * @param array|\stdClass $args
     *
     * @resolve void
     */
    public function notify(string $method, $args): \Generator
    {
        yield $this->protocol->notify($method, $args);
    }

    public static function create(
        Transport $transport,
        MethodRegistry $methodRegistry,
        Mapper $mapper = null
    ): self {
        $mapper = $mapper ?? MapperBuilder::create()
            ->getMapper();

        $invoker = new MapperInvoker($mapper);
        $dispatcher = new MethodRegistryDispatcher($methodRegistry, $invoker);
        $protocol = Protocol::create($transport, $dispatcher);

        return new static($protocol, $mapper);
    }
}
