<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Mapper;

use phpDocumentor\Reflection\Type;
use Tsufeki\BlancheJsonRpc\Exception\InternalException;
use Tsufeki\BlancheJsonRpc\Exception\InvalidParamsException;
use Tsufeki\BlancheJsonRpc\Exception\InvalidRequestException;
use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\BlancheJsonRpc\Exception\MethodNotFoundException;
use Tsufeki\BlancheJsonRpc\Exception\ParseException;
use Tsufeki\BlancheJsonRpc\Exception\ServerException;
use Tsufeki\KayoJsonMapper\Context\Context;
use Tsufeki\KayoJsonMapper\Exception\TypeMismatchException;
use Tsufeki\KayoJsonMapper\Exception\UnsupportedTypeException;
use Tsufeki\KayoJsonMapper\Loader\Loader;

class ExceptionLoader implements Loader
{
    const CLASSES = [
        InternalException::class,
        InvalidParamsException::class,
        InvalidRequestException::class,
        MethodNotFoundException::class,
        ParseException::class,
        ServerException::class,
    ];

    public function getSupportedTypes(): array
    {
        return ['object'];
    }

    public function load($data, Type $type, Context $context)
    {
        if ((string)$type !== '\\' . JsonRpcException::class) {
            throw new UnsupportedTypeException();
        }

        if (!is_object($data) || !($data instanceof \stdClass)) {
            throw new TypeMismatchException('object', $data);
        }

        if (!isset($data->code) || !is_int($data->code)) {
            throw new TypeMismatchException('int', $data->code);
        }

        if (!isset($data->message) || !is_string($data->message)) {
            throw new TypeMismatchException('string', $data->message);
        }

        $exceptionClass = JsonRpcException::class;
        foreach (static::CLASSES as $class) {
            if ($class::CODE_MIN <= $data->code && $data->code <= $class::CODE_MAX) {
                $exceptionClass = $class;
                break;
            }
        }

        return new $exceptionClass($data->message, $data->code, $data->data ?? null);
    }
}
