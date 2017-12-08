<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class MethodNotFoundException extends JsonRpcException
{
    const CODE_MIN = -32601;
    const CODE_MAX = -32601;
    const MESSAGE = 'Method not found';
}
