<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class InvalidRequestException extends JsonRpcException
{
    const CODE_MIN = -32600;
    const CODE_MAX = -32600;
    const MESSAGE = 'Invalid Request';
}
