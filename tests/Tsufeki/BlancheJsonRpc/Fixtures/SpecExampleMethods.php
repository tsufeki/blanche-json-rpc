<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Fixtures;

use Tsufeki\BlancheJsonRpc\Dispatcher\Dispatcher;
use Tsufeki\BlancheJsonRpc\Exception\MethodNotFoundException;
use Tsufeki\KayoJsonMapper\Mapper;
use Tsufeki\KayoJsonMapper\MapperBuilder;

class SpecExampleMethods
{
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

    public function getRequests(): array
    {
        return [
            'subtract',
            'sum',
            'get_data',
        ];
    }

    public function getNotifications(): array
    {
        return [
            'update',
            'notify_hello',
            'notify_sum',
        ];
    }
}
