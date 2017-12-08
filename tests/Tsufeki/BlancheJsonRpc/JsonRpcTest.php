<?php declare(strict_types=1);

namespace Tests\Tsufeki\BlancheJsonRpc;

use PHPUnit\Framework\Constraint\JsonMatches;
use PHPUnit\Framework\TestCase;
use Recoil\React\ReactKernel;
use Tests\Tsufeki\BlancheJsonRpc\Fixtures\SpecExampleDispatcher;
use Tsufeki\BlancheJsonRpc\JsonRpc;
use Tsufeki\BlancheJsonRpc\Transport\Transport;

/**
 * @coversNothing
 */
class JsonRpcTest extends TestCase
{
    /**
     * @dataProvider spec_examples
     */
    public function test_server(string $request, string $response = null)
    {
        $transport = $this->createMock(Transport::class);
        $transport
            ->expects($response !== null ? $this->once() : $this->never())
            ->method('send')
            ->with(new JsonMatches($response ?? ''));

        $dispatcher = new SpecExampleDispatcher();
        $rpc = JsonRpc::create($transport, $dispatcher);

        ReactKernel::start(function () use ($rpc, $request) {
            yield $rpc->receive($request);
        });
    }

    public function spec_examples(): array
    {
        return [
            [
                '{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}',
                '{"jsonrpc": "2.0", "result": 19, "id": 1}',
            ],
            [
                '{"jsonrpc": "2.0", "method": "update", "params": [1,2,3,4,5]}',
                null,
            ],
        ];
    }
}
