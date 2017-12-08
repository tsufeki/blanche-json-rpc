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
class SpecExampleTest extends TestCase
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
        // This data is taken from JSON-RPC 2.0 specification.

        // Copyright (C) 2007-2010 by the JSON-RPC Working Group
        //
        // This document and translations of it may be used to implement
        // JSON-RPC, it may be copied and furnished to others, and derivative
        // works that comment on or otherwise explain it or assist in its
        // implementation may be prepared, copied, published and distributed,
        // in whole or in part, without restriction of any kind, provided that
        // the above copyright notice and this paragraph are included on all
        // such copies and derivative works. However, this document itself may
        // not bemodified in any way.
        //
        // The limited permissions granted above are perpetual and will not be
        // revoked.
        //
        // This document and the information contained herein is provided "AS
        // IS" and ALL WARRANTIES, EXPRESS OR IMPLIED are DISCLAIMED, INCLUDING
        // BUT NOT LIMITED TO ANY WARRANTY THAT THE USE OF THE INFORMATION
        // HEREIN WILL NOT INFRINGE ANY RIGHTS OR ANY IMPLIED WARRANTIES OF
        // MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE.

        return [
            [
                '{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}',
                '{"jsonrpc": "2.0", "result": 19, "id": 1}',
            ],
            [
                '{"jsonrpc": "2.0", "method": "subtract", "params": [23, 42], "id": 2}',
                '{"jsonrpc": "2.0", "result": -19, "id": 2}',
            ],
            [
                '{"jsonrpc": "2.0", "method": "subtract", "params": {"subtrahend": 23, "minuend": 42}, "id": 3}',
                '{"jsonrpc": "2.0", "result": 19, "id": 3}',
            ],
            [
                '{"jsonrpc": "2.0", "method": "subtract", "params": {"minuend": 42, "subtrahend": 23}, "id": 4}',
                '{"jsonrpc": "2.0", "result": 19, "id": 4}',
            ],
            [
                '{"jsonrpc": "2.0", "method": "update", "params": [1,2,3,4,5]}',
                null,
            ],
            [
                '{"jsonrpc": "2.0", "method": "foobar"}',
                null,
            ],
            [
                '{"jsonrpc": "2.0", "method": "foobar", "id": "1"}',
                '{"jsonrpc": "2.0", "error": {"code": -32601, "message": "Method not found"}, "id": "1"}',
            ],
            [
                '{"jsonrpc": "2.0", "method": "foobar, "params": "bar", "baz]',
                '{"jsonrpc": "2.0", "error": {"code": -32700, "message": "Parse error"}, "id": null}',
            ],
            [
                '{"jsonrpc": "2.0", "method": 1, "params": "bar"}',
                '{"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null}',
            ],
            [
                '[
                    {"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},
                    {"jsonrpc": "2.0", "method"
                ]',
                '{"jsonrpc": "2.0", "error": {"code": -32700, "message": "Parse error"}, "id": null}',
            ],
            [
                '[]',
                '{"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null}',
            ],
            [
                '[1]',
                '[
                    {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null}
                ]',
            ],
            [
                '[1,2,3]',
                '[
                    {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null},
                    {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null},
                    {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null}
                ]',
            ],
            [
                '[
                    {"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},
                    {"jsonrpc": "2.0", "method": "notify_hello", "params": [7]},
                    {"jsonrpc": "2.0", "method": "subtract", "params": [42,23], "id": "2"},
                    {"foo": "boo"},
                    {"jsonrpc": "2.0", "method": "foo.get", "params": {"name": "myself"}, "id": "5"},
                    {"jsonrpc": "2.0", "method": "get_data", "id": "9"}
                ]',
                '[
                    {"jsonrpc": "2.0", "result": 7, "id": "1"},
                    {"jsonrpc": "2.0", "result": 19, "id": "2"},
                    {"jsonrpc": "2.0", "error": {"code": -32600, "message": "Invalid Request"}, "id": null},
                    {"jsonrpc": "2.0", "error": {"code": -32601, "message": "Method not found"}, "id": "5"},
                    {"jsonrpc": "2.0", "result": ["hello", 5], "id": "9"}
                ]',
            ],
            [
                '[
                    {"jsonrpc": "2.0", "method": "notify_sum", "params": [1,2,4]},
                    {"jsonrpc": "2.0", "method": "notify_hello", "params": [7]}
                ]',
                null,
            ],
        ];
    }
}
