<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Mapper;

use Tsufeki\BlancheJsonRpc\Exception\JsonRpcException;
use Tsufeki\KayoJsonMapper\Context\Context;
use Tsufeki\KayoJsonMapper\Dumper\Dumper;
use Tsufeki\KayoJsonMapper\Exception\UnsupportedTypeException;

class ExceptionDumper implements Dumper
{
    public function dump($value, Context $context)
    {
        if (!is_object($value) || !($value instanceof JsonRpcException)) {
            throw new UnsupportedTypeException();
        }

        $data = new \stdClass();
        $data->code = $value->getCode();
        $data->message = $value->getMessage();
        if ($value->getData() !== null) {
            $data->data = $value->getData();
        }

        return $data;
    }
}
