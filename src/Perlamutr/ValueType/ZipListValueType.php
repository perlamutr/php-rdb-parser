<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Exception\BrokenData;
use Perlamutr\Exception\BrokenInnerData;
use Perlamutr\FormatBinary;
use Perlamutr\Reader\Reader;
use Generator;

class ZipListValueType extends ValueType
{
    /**
     * @param Reader $reader
     *
     * @return mixed Array for Commands but mixed for ValueType
     */
    public function getData(Reader $reader): Generator
    {
        $ziplist = $reader->readString();
        $entriesCount = $this->unpackUShortLittleEndian($ziplist, 8);
        $skipBytes = 10;
        for ($i = 0; $i < $entriesCount; $i++) {
            try {
                yield $this->getEntry($ziplist, $skipBytes);
            } catch (BrokenData $e) {
                throw new BrokenInnerData(
                    'Cannot parse ziplist "'
                    . FormatBinary::instance()->getReadable($ziplist) . '"',
                    $e->getCode(),
                    $e
                );
            }
        }

        if (ord($ziplist[$skipBytes]) !== 255) {
            throw new BrokenInnerData('ZipList finished with wrong value (not 0xFF)');
        }
    }

    public function skipData(Reader $reader): void
    {
        $reader->skipString();
    }

    /**
     * @param string $ziplist
     * @param int $skipBytes
     *
     * @return int|string
     */
    private function getEntry(string &$ziplist, int &$skipBytes)
    {
        $this->checkOffset($ziplist, $skipBytes);
        if (ord($ziplist[$skipBytes++]) === 254) {
            //  Skip 4 bytes for len prev value
            $skipBytes += 4;
        }

        $this->checkOffset($ziplist, $skipBytes);
        $specialFlag = ord($ziplist[$skipBytes++]);

        //  ??xxxxxx
        switch ($specialFlag >> 6) {
            case 0x00:  //  00xxxxxx
                $len = $specialFlag & 0x3F;
                $skipBytes += $len;

                return substr($ziplist, $skipBytes - $len, $len);

            case 0x01:  //  01xxxxxx
                $this->checkOffset($ziplist, $skipBytes);
                $len = (($specialFlag & 0x3F) << 8) | ord($ziplist[$skipBytes++]);
                $skipBytes += $len;

                return substr($ziplist, $skipBytes - $len, $len);

            case 0x02:  //  10xxxxxx
                $skipBytes += 4;

                return $this->unpackUIntLittleEndian(substr($ziplist, $skipBytes - 4, 4));
        }
        //  11??xxxx
        switch (($specialFlag >> 4) & 0x03) {
            case 0x00:  //  1100xxxx:   Read next 2 bytes as a 16 bit signed integer
                $skipBytes += 2;

                return $this->unpackSShortLittleEndian($ziplist, $skipBytes - 2);

            case 0x01:  //  1101xxxx:   Read next 4 bytes as a 32 bit signed integer
                $skipBytes += 4;

                return $this->unpackSIntLittleEndian($ziplist, $skipBytes - 4);

            case 0x02:  //  1110xxxx:   Read next 8 bytes as a 64 bit signed integer
                $skipBytes += 8;

                return $this->unpackSLongLittleEndian($ziplist, $skipBytes - 8);
        }

        //  1111????
        $specialFlag &= 0x0F;
        switch ($specialFlag) {
            case 0x00:  //  11110000:   Read next 3 bytes as a 24 bit signed integer
                $this->checkOffset($ziplist, $skipBytes + 3);
                $unpacked = unpack('V', substr($ziplist, $skipBytes, 3) . chr(0))[1];
                $skipBytes += 3;

                return ($unpacked > (1 << 23)) ? $unpacked - (1 << 24) : $unpacked;

            case 0x0E:  //  11111110:   Read next byte as an 8 bit signed integer
                return unpack('c', $ziplist[$skipBytes++])[1];

            case 0x0F:  //  11111111:   FF is error
                throw new BrokenInnerData('Wrong special flag: cannot be 0xFF');
        }

        return $specialFlag - 1;
    }

    private function checkOffset(string $string, int $offset): void
    {
        if (!isset($string[$offset])) {
            throw new BrokenData("Offset $offset in ziplist entry is invalid");
        }
    }
}
