# Redis dump file (RDB) parser

[![Latest Stable Version](https://poser.pugx.org/perlamutr/php-rdb-parser/v/stable)](https://packagist.org/packages/perlamutr/php-rdb-parser) [![Total Downloads](https://poser.pugx.org/perlamutr/php-rdb-parser/downloads)](https://packagist.org/packages/perlamutr/php-rdb-parser) [![Latest Unstable Version](https://poser.pugx.org/perlamutr/php-rdb-parser/v/unstable)](https://packagist.org/packages/perlamutr/php-rdb-parser) [![License](https://poser.pugx.org/perlamutr/php-rdb-parser/license)](https://packagist.org/packages/perlamutr/php-rdb-parser)

PHP Implementation of Redis RDB parser

## Installation

Use [composer](https://getcomposer.org/download/) :

```bash
composer require perlamutr/php-rdb-parser
```

## Usage

All you need is two objects: `ReaderFile` to read from rdb-file and `Parser` to fetch its keys and values

```php
<?php
use Perlamutr\Reader\ReaderFile;
use Perlamutr\Parser;

$reader = new ReaderFile('filename.rdb');
$parser = new Parser($reader);
$generator = $parser->parseRDB();

foreach ($generator as $key => $value) {
    if (is_object($key)) {
        //  if parser meets command it returns $key as object of type Command
        continue;
    }
    //  Otherwise it contains the key
    echo "Key = '$key'\n";
    //  And value is a Generator with key-value pairs (or single value)
    foreach ($value as $k => $v) {
        echo "\t$k => $v\n";
    }
}

```

If may call `setSkipData` before `parseRDB` method with `true` argument. Parser will skip as much as he can and will return Generator with keys and additional information for each of them such as wasted bytes, type of key and file position  

```php
<?php
use Perlamutr\Reader\ReaderFile;
use Perlamutr\Parser;

$reader = new ReaderFile('filename.rdb');
$parser = new Parser($reader);
$parser->setSkipData(true);
$generator = $parser->parseRDB();
foreach ($generator as $key => $value) {
    //  $key is always string with key name
    echo "Key = '$key'\tType = '{$value['type']}\tBytes = '{$value['skip']}'\tPosition = '{$value['position']}'\n";    
}

```
