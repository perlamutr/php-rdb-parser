<?php
declare(strict_types=1);

namespace Perlamutr;

use Perlamutr\Reader\Reader;

interface DataFetcherInterface
{
    /**
     * @param Reader $reader
     *
     * @return mixed Array for Commands but mixed for ValueType
     */
    public function getData(Reader $reader);

    /**
     * Skips bytes of data for current type of object
     *
     * @param Reader $reader
     */
    public function skipData(Reader $reader): void;
}
