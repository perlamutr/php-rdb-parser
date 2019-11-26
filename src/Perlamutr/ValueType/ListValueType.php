<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Reader\Reader;
use Generator;

class ListValueType extends ValueType
{
    public function getData(Reader $reader): Generator
    {
        $len = $reader->readLen()->getLength();
        for ($i = 0; $i < $len; $i++) {
            yield $reader->readString();
        }
    }

    /**
     * Skips bytes of data for current type of object
     *
     * @param Reader $reader
     */
    public function skipData(Reader $reader): void
    {
        $len = $reader->readLen()->getLength();
        for ($i = 0; $i < $len; $i++) {
            $reader->skipString();
        }
    }
}
