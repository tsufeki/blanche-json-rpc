<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Dispatcher;

use Tsufeki\BlancheJsonRpc\Exception\InvalidParamsException;

interface Invoker
{
    /**
     * @throws InvalidParamsException
     *
     * @resolve mixed
     */
    public function invoke(callable $callable, $args): \Generator;
}
