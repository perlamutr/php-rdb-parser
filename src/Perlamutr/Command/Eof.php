<?php
declare(strict_types=1);

namespace Perlamutr\Command;

use Perlamutr\Reader\Reader;

class Eof extends Command
{
    public function getData(Reader $reader): array
    {
        return ['checksum' => $reader->read(8)];
    }

    public function skipData(Reader $reader): void
    {
        $reader->seekForward(8);
    }
}
