<?php
declare(strict_types=1);

namespace Perlamutr\Command;

use Perlamutr\Reader\Reader;

class SelectDb extends Command
{
    public function getData(Reader $reader): array
    {
        return ['database' => $reader->readLen()->getLength()];
    }

    public function skipData(Reader $reader): void
    {
        $reader->readLen();
    }
}
