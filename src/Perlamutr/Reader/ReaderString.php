<?php
declare(strict_types=1);

namespace Perlamutr\Reader;

use Perlamutr\Exception\BrokenData;
use Perlamutr\Length;

class ReaderString implements Reader
{
    private const FILE_READ_BUFFER = 4 * 1024;

    /** @var string */
    private $source;
    /** @var string */
    private $buffer;
    /** @var bool */
    private $eof;
    /** @var int */
    private $position;
    /** @var int */
    private $prevPosition;
    /** @var LengthEncoding */
    private $lengthEncoding;
    /** @var StringEncoding */
    private $stringEncoding;

    public function __construct(string $source)
    {
        $this->source = $source;

        $this->lengthEncoding = new LengthEncoding($this);
        $this->stringEncoding = new StringEncoding($this);
    }

    public function readString(): string
    {
        return $this->stringEncoding->read();
    }

    public function readLen(): Length
    {
        return $this->lengthEncoding->readLen();
    }

    public function read(int $bytesCount): ?string
    {
        $this->prevPosition = $this->position;
        $this->position += $bytesCount;

        return substr($this->source, $this->position - $bytesCount, $bytesCount);
    }

    public function readOrd(): int
    {
        return ord($this->read(1));
    }

    public function readFiveBytesLen(): int
    {
        $len = ord($this->read(1));

        if ($len === 254) {
            throw new BrokenData('Bad 5-bytes length first byte value');
        }

        return ($len === 253) ? unpack('V', $this->read(4))[1] : $len;
    }

    public function seekForward(int $bytesCount): int
    {
        $this->prevPosition = $this->position;
        $this->position += $bytesCount;

        return $this->position;
    }

    public function seekTo(int $position): void
    {
        $this->prevPosition = $this->position;
        $this->position = $position;
    }

    public function skipString(): int
    {
        $len = $this->readLen();
        if ($len->getType()->isInteger() || !$len->getLength()) {
            return $len->getBytes();
        }

        $this->seekForward($len->getLength());

        return $len->getLength() + $len->getBytes();
    }

    public function hasData(): bool
    {
        return isset($this->source[$this->position]);
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPrevPosition(): ?int
    {
        return $this->prevPosition;
    }
}
