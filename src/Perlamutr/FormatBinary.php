<?php
declare(strict_types=1);

namespace Perlamutr;

use Perlamutr\Reader\Reader;

class FormatBinary
{
    /** @var self */
    private static $instance;

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getReadable(string $binary, int $count = null, int $offset = 0): string
    {
        if ($count || $offset) {
            $binary = substr($binary, $offset, $count);
        }
        $len = strlen($binary);
        $result = '';
        $string = '';
        $hasBinString = false;
        for ($i = 0; $i < $len; $i++) {
            $symbol = $binary[$i];
            if (preg_match('/[a-zA-Z0-9_\-:,.=+\/]/', $symbol)) {
                $string .= $symbol;
                $hasBinString = true;
                continue;
            }
            if ($hasBinString) {
                $result .= $string . ' (0x' . implode(' ', str_split(bin2hex($string), 2)) . ') ';
            }

            $result .= '0x' . bin2hex($symbol) . ' ';
        }

        return rtrim($result);
    }

    /**
     * @param string $input
     * @param int $errPos
     *
     * @return string[] Format type name to list of values, eg. 'dec' => ['123', '33', '79', ...]
     */
    public function formatFromString(string $input, int $errPos): array
    {
        $format = [];
        $len = strlen($input);
        for ($i = 0; $i < $len; $i++) {
            $symbol = $input[$i];
            $format['hex'][] = ' ' . bin2hex($symbol);
            $format['bin'][] = (ctype_alnum($symbol) || ctype_punct($symbol)) ? ' ' . $symbol . ' ' : '---';
            $format['dec'][] = str_pad((string) ord($symbol), 3, ' ', STR_PAD_LEFT);
        }

        $lines = array_fill_keys(array_keys($format), '');

        foreach ($format as $t => $vals) {
            $lines[$t] .= ($errPos ? implode(' ', array_slice($vals, 0, $errPos)) . ' ' : '')
                . '[' . $vals[$errPos] . '] '
                . implode(' ', array_slice($vals, $errPos + 1));
        }

        return $lines;
    }

    /**
     * @param Reader $reader
     *
     * @return string[] Type format to values
     */
    public function formatFromPrevPosition(Reader $reader): array
    {
        $errPos = $reader->getPrevPosition();
        $from = max(0, $errPos - 10);
        $errPos = min(10, $errPos);
        $reader->seekTo($from);
        $input = $reader->read(20);

        return $this->formatFromString($input, $errPos);
    }
}
