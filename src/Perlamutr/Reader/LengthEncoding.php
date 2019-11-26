<?php
declare(strict_types=1);

namespace Perlamutr\Reader;

use Perlamutr\Exception\LengthRead;
use Perlamutr\Length;

class LengthEncoding
{
    public const LEN_ORDINARY   = 1;
    public const LEN_INTEGER    = 2;
    public const LEN_LZF        = 3;

    private const SIX_MINOR_BIT_MASK    = 0x3F;

    private const SHORT_LEN     = 0x00;
    private const ONE_MORE_BYTE = 0x01;
    private const FULL_INT      = 0x02;
    private const SPECIAL       = 0x03;

    private const ENC_INT8      = 0x00;
    private const ENC_INT16     = 0x01;
    private const ENC_INT32     = 0x02;
    private const ENC_LZF       = 0x03;

    /** @var Reader */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function readLen(): Length
    {
        $pos = $this->reader->getPosition();
        $byte = $this->reader->readOrd();
        switch ($byte >> 6) {
            case self::SHORT_LEN:
                return new Length($byte, self::LEN_ORDINARY, 1);

            case self::ONE_MORE_BYTE:
                return new Length(
                    ($byte & self::SIX_MINOR_BIT_MASK) << 8 | $this->reader->readOrd(),
                    self::LEN_ORDINARY,
                    2,
                );

            case self::FULL_INT:
                return new Length(unpack('N', $this->reader->read(4))[1], self::LEN_ORDINARY, 5);

            case self::SPECIAL:
                switch ($byte & self::SIX_MINOR_BIT_MASK) {
                    case self::ENC_INT8:
                        return new Length(unpack('c', $this->reader->read(1))[1], self::LEN_INTEGER, 2);

                    case self::ENC_INT16:
                        return new Length(unpack('s', $this->reader->read(2))[1], self::LEN_INTEGER, 3);

                    case self::ENC_INT32:
                        return new Length(unpack('l', $this->reader->read(4))[1], self::LEN_INTEGER, 5);

                    case self::ENC_LZF:
                        $comp = $this->reader->readLen();
                        $decomp = $this->reader->readLen();

                        return new Length(
                            $comp->getLength(),
                            self::LEN_LZF,
                            $comp->getBytes() + $decomp->getBytes() + 1,
                            $decomp->getLength()
                        );
                }
        }

        throw new LengthRead("Cannot read length value from position $pos");
    }
}
