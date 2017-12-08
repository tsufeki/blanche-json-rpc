<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Exception;

class ServerException extends JsonRpcException
{
    const CODE_MIN = -32099;
    const CODE_MAX = -32000;
    const MESSAGE = 'Server error';
}
