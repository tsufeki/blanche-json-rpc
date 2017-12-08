<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class ParseException extends JsonRpcException
{
    const CODE_MIN = -32700;
    const CODE_MAX = -32700;
    const MESSAGE = 'Parse error';
}
