<?php
declare(strict_types=1);

namespace Perlamutr;

class Header
{
    /** @var string */
    private $redisWord;
    /** @var int */
    private $version;
    /** @var int */
    private $headerLen;

    public function __construct(string $header)
    {
        $this->parse($header);
    }

    public function ok(): bool
    {
        return ($this->redisWord === 'REDIS' && $this->version > 0 && $this->version <= 9 && $this->headerLen === 9);
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    private function parse(string $header): void
    {
        $this->redisWord = substr($header, 0, 5);
        $this->version = (int) substr($header, 5, 4);
        $this->headerLen = strlen($header);
    }
}
