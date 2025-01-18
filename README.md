# json-php

JSON helper functions for PHP.

## Installation

You can require it directly with Composer:

```bash
composer require jdwx/json-php
```

Or download the source from GitHub: https://github.com/jdwx/json-php.git

## Requirements

This module requires PHP 8.3 or later and the JSON extension.

## Usage

Here is a basic usage example:

```php

use JDWX\Json\Json;

$data = Json::decode( '{"a":1,"b":2}' );
var_dump( $data );

$json = Json::encode( $data );
echo $json, "\n";

$json = Json::encodePretty( $data );
echo $json, "\n";

```

This module also supports streaming JSON decoding, such as reading from
a JsonLines file:

```php

use JDWX\Json\JsonLines;

$stream = fopen( 'file.jsonl', 'r' );
while( $data = JsonLines::decodeFile( $stream ) ) {
    var_dump( $data );
}

```

This module also includes:

* lower-level interfaces for streaming JSON decoding that allow (among other things) reading the elements of a JSON list individually without loading the whole list into memory
* a feature-complete JSON lexer that breaks input into discrete JSON elements

Most of the streaming functionality is designed to support cases where JSON input might be problematically large or when it is coming from a potentially endless source, such as a network connection.

There is extensive test coverage for this module, which provides additional examples of usage.

## Stability

This module is considered stable and is extensively used in production code.

## History

This module was refactored out of a larger codebase in November 2024.
