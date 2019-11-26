<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Reader\Reader;
use Generator;

class HashZiplistValueType extends ZipListValueType
{
    public function getData(Reader $reader): Generator
    {
        $key = null;
        foreach (parent::getData($reader) as $val) {
            if ($key === null) {
                $key = $val;
            } else {
                yield $key => $val;
                $key = null;
            }
        }
    }
}
