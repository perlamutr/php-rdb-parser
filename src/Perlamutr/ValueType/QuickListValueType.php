<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Reader\Reader;
use Generator;

class QuickListValueType extends ZipListValueType
{
    public function getData(Reader $reader): Generator
    {
        for ($len = $reader->readLen()->getLength(); $len; $len--) {
            yield from parent::getData($reader);
        }
    }

    public function skipData(Reader $reader): void
    {
        for ($len = $reader->readLen()->getLength(); $len; $len--) {
            parent::skipData($reader);
        }
    }
}
