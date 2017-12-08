<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class InvalidParamsException extends JsonRpcException
{
    const CODE_MIN = -32602;
    const CODE_MAX = -32602;
    const MESSAGE = 'Invalid params';
}
