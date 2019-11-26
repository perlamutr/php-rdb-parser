<?php
declare(strict_types=1);

namespace Perlamutr;

use Perlamutr\Reader\Reader;
use Perlamutr\Command\Command;
use Perlamutr\Exception\UnknownHeader;
use Perlamutr\ValueType\ValueType;
use Generator;

class Parser
{
    /** @var Reader */
    private $reader;
    /** @var bool */
    private $skipHeader = false;
    /** @var bool */
    private $skipData = false;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function parseRDB(): Generator
    {
        if (!$this->skipHeader) {
            $this->checkHeader();
        }

        while ($this->reader->hasData()) {
            $cmdByte = $this->reader->readOrd();

            $command = Command::create($cmdByte);
            if ($command) {
                if ($this->skipData) {
                    $command->skipData($this->reader);
                    continue;
                }
                yield $command => $command->getData($this->reader);
                continue;
            }

            $valueType = ValueType::get($cmdByte);

            $keyName = $this->reader->readString();

            if ($this->skipData) {
                $pos = $this->reader->getPosition();
                $valueType->skipData($this->reader);
                $skipped = $this->reader->getPosition() - $pos;

                yield $keyName => ['type' => get_class($valueType), 'skip' => $skipped, 'pos' => $pos];
            } else {
                yield $keyName => $valueType->getData($this->reader);
            }
        }
    }

    public function seekTo(int $startFrom): void
    {
        $this->skipHeader = $startFrom > 0;
        $this->reader->seekTo($startFrom);
    }

    public function setSkipData(bool $skipData = true): bool
    {
        $prev = $this->skipData;
        $this->skipData = $skipData;

        return $prev;
    }

    private function checkHeader(): void
    {
        $headerData = $this->reader->read(9);
        $header = new Header($headerData);
        if (!$header->ok()) {
            throw new UnknownHeader('Bad header data: 0x' . bin2hex($headerData));
        }
    }
}
