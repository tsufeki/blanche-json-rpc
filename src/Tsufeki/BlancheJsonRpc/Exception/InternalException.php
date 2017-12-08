<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class InternalException extends JsonRpcException
{
    const CODE_MIN = -32603;
    const CODE_MAX = -32603;
    const MESSAGE = 'Internal error';
}
