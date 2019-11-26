<?php
declare(strict_types=1);

namespace Perlamutr\Command;

use Perlamutr\Reader\Reader;

class ExpireTimeMs extends Command
{
    public function getData(Reader $reader): array
    {
        return ['ts' => unpack('J', $reader->read(8))[1] / 1000];
    }

    public function skipData(Reader $reader): void
    {
        $reader->seekForward(8);
    }
}
