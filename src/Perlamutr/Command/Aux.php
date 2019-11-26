<?php
declare(strict_types=1);

namespace Perlamutr\Command;

use Perlamutr\Reader\Reader;

class Aux extends Command
{
    public function getData(Reader $reader): array
    {
        return ['key' => $reader->readString(), 'value' => $reader->readString()];
    }

    public function skipData(Reader $reader): void
    {
        $reader->skipString();
        $reader->skipString();
    }
}
