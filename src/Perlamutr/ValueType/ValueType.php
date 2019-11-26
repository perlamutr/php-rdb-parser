<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\DataFetcherInterface;
use Perlamutr\Exception\BrokenData;
use Perlamutr\Exception\UnknownValueType;
use Perlamutr\Reader\Reader;
use Generator;

abstract class ValueType implements DataFetcherInterface
{
    private const STRING      = 0x00; //  String Encoding
    private const LIST        = 0x01; //  List Encoding
    private const SET         = 0x02; //  Set Encoding
    private const SORTED      = 0x03; //  Sorted Set Encoding
    private const HASH        = 0x04; //  Hash Encoding
    private const ZIPMAP      = 0x09; //  Zipmap Encoding
    private const ZIPLIST     = 0x0A; //  Ziplist Encoding
    private const INTSET      = 0x0B; //  Intset Encoding
    private const SSZL        = 0x0C; //  Sorted Set in Ziplist Encoding
    private const HMZL        = 0x0D; //  Hashmap in Ziplist Encoding (RDB >= 4)
    private const QUICK       = 0x0E; //  Quicklist is a linked list of ziplists (RDB >= 7)

    /** @var self[] */
    private static $instances = [];

    public static function get(int $valueType): self
    {
        $instance =& self::$instances[$valueType];
        if (!isset($instance)) {
            self::createInstance($valueType);
        }

        return self::$instances[$valueType];
    }

    private static function createInstance(int $valueType): self
    {
        switch ($valueType) {
            case self::STRING:
                return self::$instances[$valueType] = new StringValueType();

            case self::LIST:
                return self::$instances[$valueType] = new ListValueType();

            case self::SET:
                return self::$instances[$valueType] = new SetValueType();

            case self::SORTED:
                return self::$instances[$valueType] = new SortedSetValueType();

            case self::HASH:
                return self::$instances[$valueType] = new HashValueType();

            case self::ZIPMAP:
                return self::$instances[$valueType] = new ZipmapValueType();

            case self::ZIPLIST:
                return self::$instances[$valueType] = new ZipListValueType();

            case self::INTSET:
                return self::$instances[$valueType] = new IntSetValueType();

            case self::SSZL:
                return self::$instances[$valueType] = new SortedZiplistValueType();

            case self::HMZL:
                return self::$instances[$valueType] = new HashZiplistValueType();

            case self::QUICK:
                return self::$instances[$valueType] = new QuickListValueType();

            default:
                throw new UnknownValueType('Unknown value type ' . $valueType);
        }
    }

    protected function unpackUIntLittleEndian(string $data, int $offset = 0): int
    {
        return $this->unpack('V', 4, $data, $offset);
    }

    protected function unpackUShortLittleEndian(string $data, int $offset = 0): int
    {
        return $this->unpack('v', 2, $data, $offset);
    }

    protected function unpackSShortLittleEndian(string $data, int $offset = 0): int
    {
        return $this->unpackSigned('v', 2, $data, $offset);
    }

    protected function unpackSIntLittleEndian(string $data, int $offset = 0): int
    {
        return $this->unpackSigned('V', 4, $data, $offset);
    }

    protected function unpackSLongLittleEndian(string $data, int $offset = 0): int
    {
        $bytes = (isset($data[8]) || $offset) ? substr($data, $offset, 8) : $data;

        return unpack('P', $bytes)[1];
    }

    private function unpack(string $format, int $length, string $data, int $offset): int
    {
        $cut = substr($data, $offset, $length);
        if (strlen($cut) !== $length) {
            throw new BrokenData("Cannot unpack with '$format': insufficient data (need $length bytes)");
        }

        return unpack($format, (isset($data[$length]) || $offset) ? $cut : $data)[1];
    }

    private function unpackSigned(string $format, int $length, string $data, int $offset): int
    {
        $bytes = (isset($data[$length]) || $offset) ? substr($data, $offset, $length) : $data;
        if (strlen($bytes) !== $length) {
            throw new BrokenData("Cannot unpack signed with '$format': insufficient data (need $length bytes)");
        }
        $unpacked = unpack($format, $bytes)[1];

        return ($unpacked & (0xC0 << (($length - 1) * 8))) ? $unpacked - (1 << ($length * 8)) : $unpacked;
    }

    abstract public function getData(Reader $reader): Generator;
}
