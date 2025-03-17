# NomaJS

A PHP to JS transpiler. 

## Installation

```shell
composer require noma/js
```

## Usage

There's lots to write, but the most basic usage looks like this:

```php
use Noma\Js\Js;

$js = Js::fromString('<?php echo "Hello, World";');

// $js becomes => console.log("Hello, World");
```

You can also use `Js::fromFile`. For more info please refer to the [examples](https://github.com/nomaphp/js/tree/main/examples) for now.