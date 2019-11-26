<?php
declare(strict_types=1);

namespace Perlamutr\Command;

use Perlamutr\Reader\Reader;

class ExpireTime extends Command
{
    public function getData(Reader $reader): array
    {
        return ['ts' => (float) unpack('N', $reader->read(4))[1]];
    }

    public function skipData(Reader $reader): void
    {
        $reader->seekForward(4);
    }
}
