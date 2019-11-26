<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Reader\Reader;
use Generator;

class StringValueType extends ValueType
{

    public function getData(Reader $reader): Generator
    {
        yield $reader->readString();
    }

    public function skipData(Reader $reader): void
    {
        $reader->skipString();
    }
}
