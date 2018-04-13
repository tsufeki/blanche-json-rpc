<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

use Tsufeki\BlancheJsonRpc\Exception\InvalidParamsException;
use Tsufeki\KayoJsonMapper\Exception\MapperException;
use Tsufeki\KayoJsonMapper\Mapper;

class MapperInvoker implements Invoker
{
    /**
     * @var Mapper
     */
    private $mapper;

    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function invoke(callable $callable, $args): \Generator
    {
        try {
            $mappedArgs = $this->mapper->loadArguments($args, $callable);
        } catch (MapperException $e) {
            throw new InvalidParamsException(null, null, null, $e);
        }

        $result = $callable(...$mappedArgs);

        if ($result instanceof \Generator) {
            $result = yield $result;
        }

        return $this->mapper->dump($result);
    }
}
