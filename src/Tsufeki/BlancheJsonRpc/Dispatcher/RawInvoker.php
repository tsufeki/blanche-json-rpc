<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

class RawInvoker implements Invoker
{
    public function invoke(callable $callable, $args): \Generator
    {
        $result = $callable($args);

        if ($result instanceof \Generator) {
            $result = yield $result;
        }

        return $result;
    }
}
