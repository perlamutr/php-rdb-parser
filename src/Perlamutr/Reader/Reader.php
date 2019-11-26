<?php
declare(strict_types=1);

namespace Perlamutr\Reader;

use Perlamutr\Length;

interface Reader
{
    public function readString(): string;

    public function readLen(): Length;

    public function read(int $bytesCount): ?string;

    public function readOrd(): int;

    public function readFiveBytesLen(): int;

    public function seekForward(int $bytesCount): int;

    public function seekTo(int $position): void;

    public function skipString(): int;

    public function hasData(): bool;

    public function getPosition(): int;

    public function getPrevPosition(): ?int;
}
