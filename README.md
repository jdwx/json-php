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

There is also 100% test coverage for this module, which provides additional examples of usage.

## Stability

This module is considered stable and is extensively used in production code.

## History

This module was refactored out of a larger codebase in November 2024.
