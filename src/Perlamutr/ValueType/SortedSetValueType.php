<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Reader\Reader;
use Generator;

class SortedSetValueType extends ValueType
{
    public const INF_PLUS   = '+inf';
    public const INF_MINUS  = '-inf';
    public const NAN        = 'NaN';

    public function getData(Reader $reader): Generator
    {
        $len = $reader->readLen()->getLength();
        for ($i = 0; $i < $len; $i++) {
            $key = $reader->readString();
            $scoreLen = $reader->readOrd();
            switch ($scoreLen) {
                case 253:
                    $score = self::NAN;
                    break;

                case 254:
                    $score = self::INF_PLUS;
                    break;

                case 255:
                    $score = self::INF_MINUS;
                    break;

                default:
                    $score = (int) $reader->read($scoreLen);
                    break;
            }
            yield $key => $score;
        }
    }

    public function skipData(Reader $reader): void
    {
        $len = $reader->readLen()->getLength();
        for ($i = 0; $i < $len; $i++) {
            //  Skip value name
            $reader->skipString();
            //  Skip score
            $scoreLen = $reader->readOrd();
            if ($scoreLen < 253) {
                $reader->seekForward($scoreLen);
            }
        }
    }
}
