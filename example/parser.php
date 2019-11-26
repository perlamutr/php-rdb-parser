<?php
declare(strict_types=1);

use Perlamutr\Command\Command;
use Perlamutr\Exception\BrokenInnerData;
use Perlamutr\FormatBinary;
use Perlamutr\Reader\ReaderFile;
use Perlamutr\Parser;

require_once __DIR__ . '/../vendor/autoload.php';

$reader = new ReaderFile($_SERVER['argv'][1]);
$parser = new Parser($reader);
$parser->setSkipData(false);    //  defaults is false too, no need to call
$keys = $parser->parseRDB();

try {
    foreach ($keys as $k => $info) {
        if (is_object($k)) {
            /** @var Command $k */
            echo '-> ' . get_class($k) . "\n";
        } else {
            echo "[$k, " . $reader->getPrevPosition() . "]\n";
        }
        foreach ($info as $kk => $vv) {
            echo "\t$kk => $vv\n";
        }
    }
} catch (BrokenInnerData $e) {
    echo "\n\nInner data is broken KEY=$k\n";
    if ($e->getPrevious()) {
        echo 'Previous error is ' . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "\n\nERROR " . $e->getMessage() . ' AT POS=' . $reader->getPrevPosition() . ":\n";
    $format = new FormatBinary();
    $lines = $format->formatFromPrevPosition($reader);

    foreach ($lines as $t => $line) {
        echo "$t:\t$line\n";
    }
}
