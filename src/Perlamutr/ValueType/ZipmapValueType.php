<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Exception\BrokenData;
use Perlamutr\Reader\Reader;
use Generator;

class ZipmapValueType extends ValueType
{
    public function getData(Reader $reader): Generator
    {
        $content = $reader->readString();
        $len = ord($content[0]);
        $len =  ($len >= 254) ? PHP_INT_MAX : (int) floor($len / 2);

        for ($i = 0; $i < $len; $i++) {
            try {
                $keyLen = $reader->readFiveBytesLen();
            } catch (BrokenData $e) {
                if ($len === PHP_INT_MAX) {
                    return;
                }

                throw $e;
            }
            $key = $reader->read($keyLen);
            $valueLen = $reader->readFiveBytesLen();
            $value = $reader->read($valueLen);

            yield $key => $value;
        }

        if ($reader->readOrd() !== 255) {
            throw new BrokenData('No zmend at the end of ZipMap');
        }
    }

    /**
     * Skips bytes of data for current type of object
     *
     * @param Reader $reader
     */
    public function skipData(Reader $reader): void
    {
        $reader->skipString();
    }
}
