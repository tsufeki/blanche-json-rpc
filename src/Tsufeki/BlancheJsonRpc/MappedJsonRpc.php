<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc;

use Psr\Log\LoggerInterface;
use Tsufeki\BlancheJsonRpc\Dispatcher\MapperInvoker;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistry;
use Tsufeki\BlancheJsonRpc\Dispatcher\MethodRegistryDispatcher;
use Tsufeki\BlancheJsonRpc\Mapper\MapperFactory;
use Tsufeki\BlancheJsonRpc\Transport\Transport;
use Tsufeki\KayoJsonMapper\Mapper;
use Tsufeki\KayoJsonMapper\MapperBuilder;

class MappedJsonRpc
{
    /**
     * @var JsonRpc
     */
    private $rpc;

    /**
     * @var Mapper
     */
    private $mapper;

    public function __construct(JsonRpc $rpc, Mapper $mapper)
    {
        $this->rpc = $rpc;
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
        $result = yield $this->rpc->call($method, $this->mapper->dump($args));

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
        yield $this->rpc->notify($method, $this->mapper->dump($args));
    }

    public static function create(
        Transport $transport,
        MethodRegistry $methodRegistry,
        Mapper $mapper = null,
        LoggerInterface $logger = null
    ): self {
        $mapper = $mapper ?? MapperBuilder::create()->getMapper();
        $internalMapper = (new MapperFactory())->create();
        $invoker = new MapperInvoker($mapper);
        $dispatcher = new MethodRegistryDispatcher($methodRegistry, $invoker);
        $idSequence = (function (): \Generator {
            for ($i = 1;; $i++) {
                yield $i;
            }
        })();

        $rpc = new JsonRpc(
            $transport,
            $dispatcher,
            $internalMapper,
            $idSequence,
            $logger
        );

        return new static($rpc, $mapper);
    }
}
