<?php
declare(strict_types=1);

namespace Perlamutr\Command;

use Perlamutr\DataFetcherInterface;

abstract class Command implements DataFetcherInterface
{
    private const EOF           = 0xFF;
    private const SELECTDB      = 0xFE;
    private const EXPIRETIME    = 0xFD;
    private const EXPIRETIMEMS  = 0xFC;
    private const RESIZEDB      = 0xFB;
    private const AUX           = 0xFA;

    /** @var Command */
    private static $instances = [];

    /**
     * @param int|string $cmd
     * @return Command|null
     */
    public static function create($cmd): ?Command
    {
        $cmd = is_int($cmd) ? $cmd : ord($cmd);

        if (isset(self::$instances[$cmd])) {
            return self::$instances[$cmd];
        }

        switch ($cmd) {
            case self::EOF:
                self::$instances[$cmd] = new Eof();
                break;

            case self::SELECTDB:
                self::$instances[$cmd] = new SelectDb();
                break;

            case self::EXPIRETIME:
                self::$instances[$cmd] = new ExpireTime();
                break;

            case self::EXPIRETIMEMS:
                self::$instances[$cmd] = new ExpireTimeMs();
                break;

            case self::RESIZEDB:
                self::$instances[$cmd] = new ResizeDb();
                break;

            case self::AUX:
                self::$instances[$cmd] = new Aux();
                break;

            default:
                return null;
        }

        return self::$instances[$cmd];
    }

    public function __toString(): string
    {
        $name = get_class($this);

        return (string) substr($name, strrpos($name, '\\') + 1);
    }
}
