<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc;

use Tsufeki\BlancheJsonRpc\Exception\JsonException;

final class Json
{
    public static function encode($value): string
    {
        $result = json_encode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg());
        }

        return $result;
    }

    public static function decode(string $value)
    {
        $result = json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg());
        }

        return $result;
    }

    // @codeCoverageIgnoreStart
    private function __construct()
    {
    }

    // @codeCoverageIgnoreEnd
}
