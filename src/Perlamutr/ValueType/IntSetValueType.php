<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Reader\Reader;
use Generator;

class IntSetValueType extends ValueType
{
    private const ENC_TO_PACK_FORMAT = [2 => 'v', 4 => 'V', 8 => 'P'];

    public function getData(Reader $reader): Generator
    {
        $data = $reader->readString();
        $encoding = unpack('V', substr($data, 0, 4))[1];
        $format = self::ENC_TO_PACK_FORMAT[$encoding];

        $len = unpack('V', substr($data, 4, 4))[1];
        $pos = 8;
        for ($i = 0; $i < $len; $i++) {
            yield unpack($format, substr($data, $pos + $i * $encoding, $encoding));
        }
    }

    public function skipData(Reader $reader): void
    {
        $reader->skipString();
    }
}
