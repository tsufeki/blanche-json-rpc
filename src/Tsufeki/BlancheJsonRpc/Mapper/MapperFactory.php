<?php declare(strict_types=1);

namespace Tsufeki\BlancheJsonRpc\Mapper;

use Tsufeki\KayoJsonMapper\Mapper;
use Tsufeki\KayoJsonMapper\MapperBuilder;
use Tsufeki\KayoJsonMapper\NameMangler\NullNameMangler;

class MapperFactory
{
    public function create(): Mapper
    {
        return MapperBuilder::create()
            ->setNameMangler(new NullNameMangler())
            ->addDumper(new ExceptionDumper())
            ->addLoader(new ExceptionLoader())
            ->throwOnUnknownProperty(true)
            ->throwOnMissingProperty(false)
            ->getMapper();
    }
}
