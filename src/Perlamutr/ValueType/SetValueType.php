<?php
declare(strict_types=1);

namespace Perlamutr\ValueType;

use Perlamutr\Exception\LengthRead;
use Perlamutr\Reader\Reader;
use Generator;

class SetValueType extends ValueType
{
    private const SKIP_SAME_LEN_RECORDS = 100;

    private $isSameLenRecordsEnabled = true;

    public function getData(Reader $reader): Generator
    {
        $len = $reader->readLen()->getLength();
        for ($i = 0; $i < $len; $i++) {
            yield $reader->readString();
        }
    }

    public function disableSkipSameLenRecords(): void
    {
        $this->isSameLenRecordsEnabled = false;
    }

    public function skipData(Reader $reader): void
    {
        $cnt = $reader->readLen()->getLength();
        $lenPrev = null;
        $sameLen = $this->isSameLenRecordsEnabled;
        for ($i = 0; $i < $cnt; $i++) {
            $lenCur = $reader->skipString();
            if ($lenPrev === null) {
                $lenPrev = $lenCur;
            } elseif ($lenPrev !== $lenCur) {
                $sameLen = false;
            }

            if ($sameLen && $i >= self::SKIP_SAME_LEN_RECORDS && $i + self::SKIP_SAME_LEN_RECORDS < $cnt) {
                if ($this->skipSameLenValues($reader, $cnt - $i - 1, $lenCur)) {
                    return;
                }

                $sameLen = false;
            }
        }
    }

    private function skipSameLenValues(Reader $reader, int $count, int $sameLen): int
    {
        $skipBytes = ($count - 1) * $sameLen;
        $backPointer = $reader->seekForward($skipBytes);
        try {
            $checkBytes = $reader->readLen();

            if ($checkBytes->getLength() === $sameLen - 1) {
                $reader->seekForward($sameLen - 1);

                return $skipBytes + $checkBytes->getBytes() + $sameLen - 1;
            }
        } catch (LengthRead $e) {
        }

        $reader->seekTo($backPointer);

        return 0;
    }
}
