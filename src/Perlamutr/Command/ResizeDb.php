<?php
declare(strict_types=1);

namespace Perlamutr\Command;

use Perlamutr\Reader\Reader;

class ResizeDb extends Command
{
    public function getData(Reader $reader): array
    {
        return ['database' => $reader->readLen()->getLength(), 'expiry' => $reader->readLen()->getLength()];
    }

    public function skipData(Reader $reader): void
    {
        $reader->readLen();
        $reader->readLen();
    }
}
