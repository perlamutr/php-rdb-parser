<?php
declare(strict_types=1);

namespace Perlamutr;

class Length
{
    /** @var int */
    private $len;
    /** @var LengthType */
    private $type;
    /** @var int */
    private $bytes;
    /** @var int|null */
    private $decompressedSize;

    public function __construct(int $len, int $type, int $bytes, int $decompressedSize = null)
    {
        $this->len = $len;
        $this->type = LengthType::get($type);
        $this->bytes = $bytes;
        $this->decompressedSize = $decompressedSize;
    }

    public function getType(): LengthType
    {
        return $this->type;
    }

    public function getLength(): int
    {
        return $this->len;
    }

    public function getBytes(): int
    {
        return $this->bytes;
    }

    public function getDecompressedSize(): ?int
    {
        return $this->decompressedSize;
    }
}
