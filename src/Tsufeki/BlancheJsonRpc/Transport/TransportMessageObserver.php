<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Transport;

interface TransportMessageObserver
{
    public function receive(string $message): \Generator;
}
