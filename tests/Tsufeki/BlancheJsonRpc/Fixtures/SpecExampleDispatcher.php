<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Fixtures;

use Tsufeki\BlancheJsonRpc\Dispatcher\Dispatcher;
use Tsufeki\BlancheJsonRpc\Exception\MethodNotFoundException;
use Tsufeki\KayoJsonMapper\Mapper;
use Tsufeki\KayoJsonMapper\MapperBuilder;

class SpecExampleDispatcher implements Dispatcher
{
    /**
     * @var Mapper
     */
    private $mapper;

    public function __construct()
    {
        $this->mapper = MapperBuilder::create()->getMapper();
    }

    public function dispatch(string $methodName, $args): \Generator
    {
        if (!method_exists($this, $methodName)) {
            throw new MethodNotFoundException();
        }

        $mappedArgs = $this->mapper->loadArguments($args, [$this, $methodName]);

        return $this->$methodName(...$mappedArgs);
        yield;
    }

    public function subtract(int $minuend, int $subtrahend): int
    {
        return $minuend - $subtrahend;
    }

    public function update(...$a)
    {
    }

    public function sum(int ...$a): int
    {
        return array_sum($a);
    }

    public function notify_hello(int $a)
    {
    }

    public function notify_sum(int ...$a)
    {
    }

    public function get_data(): array
    {
        return ['hello', 5];
    }
}
