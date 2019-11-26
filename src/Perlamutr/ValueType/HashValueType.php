<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Reader\Reader;
use Generator;

class HashValueType extends ValueType
{
    public function getData(Reader $reader): Generator
    {
        $len = $reader->readLen()->getLength();
        for ($i = 0; $i < $len; $i++) {
            yield $reader->readString() => $reader->readString();
        }
    }

    public function skipData(Reader $reader): void
    {
        $elements = $reader->readLen()->getLength() * 2;

        for ($i = 0; $i < $elements; $i++) {
            $reader->skipString();
        }
    }
}
