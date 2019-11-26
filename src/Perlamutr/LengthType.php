<?php
declare(strict_types=1);

namespace Perlamutr;

class LengthType
{
    public const LEN_ORDINARY = 1;
    public const LEN_INTEGER = 2;
    public const LEN_LZF = 3;

    /** @var int */
    private $type;

    private static $instances = [];

    private function __construct(int $type)
    {
        $this->type = $type;
    }

    public static function get(int $type): self
    {
        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = new self($type);
        }

        return self::$instances[$type];
    }

    public function isOrdinary(): bool
    {
        return $this->type === self::LEN_ORDINARY;
    }

    public function isInteger(): bool
    {
        return $this->type === self::LEN_INTEGER;
    }

    public function isEncoded(): bool
    {
        return $this->type === self::LEN_LZF;
    }
}
