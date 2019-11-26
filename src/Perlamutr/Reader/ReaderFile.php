<?php
declare(strict_types=1);

namespace Perlamutr\Reader;

use Perlamutr\Exception\BrokenData;
use Perlamutr\Exception\FileReader;
use Perlamutr\Exception\IO;
use Perlamutr\Length;
use RuntimeException;

class ReaderFile implements Reader
{
    private const FILE_READ_BUFFER = 4 * 1024;

    /** @var resource */
    private $file;
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

    public function __construct(string $fileName)
    {
        $this->file = fopen($fileName, 'rb');
        if ($this->file === false) {
            throw new FileReader("Cannot open file $fileName");
        }
        $this->buffer = '';
        $this->eof = feof($this->file);
        $this->position = $this->prevPosition = 0;

        $this->stringEncoding = new StringEncoding($this);
        $this->lengthEncoding = new LengthEncoding($this);
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
        $bufLen = strlen($this->buffer);
        if (($bytesCount > $bufLen) && !$this->fillBuffer($bytesCount - $bufLen)) {
            return null;
        }
        $result = substr($this->buffer, 0, $bytesCount);
        $this->buffer = substr($this->buffer, $bytesCount);
        $this->prevPosition = $this->position;
        $this->position += $bytesCount;

        return $result;
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
        if (!$bytesCount) {
            return $this->position;
        }

        $offset = $bytesCount - strlen($this->buffer);

        if ($offset > 0) {
            if (fseek($this->file, $offset, SEEK_CUR) !== 0) {
                throw new IO('Unable to seek given position in file');
            }
            $this->buffer = '';
        } else {
            $this->buffer = substr($this->buffer, $bytesCount);
        }

        $this->prevPosition = $this->position;
        $this->position += $bytesCount;

        return $this->prevPosition;
    }

    public function seekTo(int $position): void
    {
        if (fseek($this->file, $position, SEEK_SET) !== 0) {
            throw new IO('Unable to seek given position in file');
        }

        $this->buffer = '';
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
        return !$this->eof;
    }

    public function getPrevPosition(): ?int
    {
        return $this->prevPosition;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    private function fillBuffer(int $bytesCount): bool
    {
        if ($this->eof) {
            return false;
        }
        $bytesReal = (int) ceil($bytesCount / self::FILE_READ_BUFFER) * self::FILE_READ_BUFFER;

        $result = fread($this->file, $bytesReal);
        $this->eof = feof($this->file);
        if (($result === false) || ((strlen($result) !== $bytesReal) && !$this->eof)) {
            throw new RuntimeException('Cannot read file');
        }

        $this->buffer .= $result;

        return true;
    }
}
