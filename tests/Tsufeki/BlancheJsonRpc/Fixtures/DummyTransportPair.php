<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc\Fixtures;

use Tsufeki\BlancheJsonRpc\Transport\Transport;
use Tsufeki\BlancheJsonRpc\Transport\TransportMessageObserver;

class DummyTransportPair implements Transport
{
    /**
     * @var self
     */
    private $other;

    /**
     * @var TransportMessageObserver
     */
    private $observer;

    public function attach(TransportMessageObserver $observer)
    {
        $this->observer = $observer;
    }

    public function send(string $message): \Generator
    {
        yield $this->other->observer->receive($message);
    }

    /**
     * @return Transport[]
     */
    public static function create(): array
    {
        $ends = [new static(), new static()];

        $ends[0]->other = $ends[1];
        $ends[1]->other = $ends[0];

        return $ends;
    }
}
