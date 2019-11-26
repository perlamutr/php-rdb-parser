<?php

namespace Perlamutr\Reader;

use Perlamutr\Exception\DecompressLZF;

class StringEncoding
{
    /** @var Reader */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function read(): string
    {
        $len = $this->reader->readLen();
        $type = $len->getType();

        if ($type->isOrdinary()) {
            $length = $len->getLength();

            return $length > 0 ? $this->reader->read($length) : '';
        }

        if ($type->isInteger()) {
            return $len->getLength();
        }

        if ($type->isEncoded()) {
            return $this->readLzf($len->getLength(), $len->getDecompressedSize());
        }
    }

    private function readLzf(int $byteCount, int $expectedUncompressed): string
    {
        $data = $this->reader->read($byteCount);
        $result = function_exists('lzf_decompress') ? lzf_decompress($data) : $this->lzfDecompress($data);

        if ($result === false) {
            throw new DecompressLZF('Failed decompressing LZF data');
        }

        $len = strlen($result);
        if ($len !== $expectedUncompressed) {
            throw new DecompressLZF('Unexpected uncompressed length: EXP=' . $expectedUncompressed . ', ACT=' . $len);
        }

        return $result;
    }

    /**
     * LZF decompress function
     * @see https://github.com/zhuyie/golzf/blob/master/lzf.go
     *
     * @param string $input string for decompressing
     *
     * @return string|null decompressed string or null on fails
     */
    private function lzfDecompress(string $input): ?string
    {
        $inputIndex = $outputIndex = 0;
        $inputLength = strlen($input);
        $output = '';

        if ($inputLength === 0) {
            return null;
        }

        while ($inputIndex < $inputLength) {
            $ctrl = ord($input[$inputIndex++]);

            if ($ctrl < (1 << 5)) {
                //  literal run
                ++$ctrl;

                if ($inputIndex + $ctrl > $inputLength) {
                    throw new DecompressLZF('Corrupted literal run section');
                }

                $output .= substr($input, $inputIndex, $ctrl);
                $outputIndex += $ctrl;
                $inputIndex += $ctrl;
            } else {
                //  back reference
                $length = $ctrl >> 5;
                $ref = $outputIndex - (($ctrl & 0x1f) << 8) - 1;

                if ($inputIndex >= $inputLength) {
                    throw new DecompressLZF('Corrupted back reference section');
                }

                if ($length === 7) {
                    $length += ord($input[$inputIndex++]);

                    if ($inputIndex >= $inputLength) {
                        throw new DecompressLZF('Corrupted back reference section');
                    }
                }

                $ref -= ord($input[$inputIndex++]);

                if ($ref < 0) {
                    throw new DecompressLZF('Corrupted back reference section');
                }

                //  it has special handling when source and destination overlap
                for ($i = 0; $i < $length + 2; $i++) {
                    $output[$outputIndex + $i] = $output[$ref + $i];
                }
                $outputIndex += $length + 2;
            }
        }

        return $output;
    }
}
