[![Latest Stable Version](https://poser.pugx.org/xepozz/stacktrace-parser/v/stable.png)](https://packagist.org/packages/xepozz/stacktrace-parser)
[![Total Downloads](https://poser.pugx.org/xepozz/stacktrace-parser/downloads.png)](https://packagist.org/packages/xepozz/stacktrace-parser)
[![Build status](https://github.com/xepozz/stacktrace-parser/workflows/build/badge.svg)](https://github.com/xepozz/stacktrace-parser/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/xepozz/stacktrace-parser/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/xepozz/stacktrace-parser/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/xepozz/stacktrace-parser/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/xepozz/stacktrace-parser/?branch=main)

# Introduction

The `stacktrace-parser` is a tool to parse string stacktrace to array like you
call [getTrace()](https://www.php.net/manual/ru/exception.gettrace.php) on a real exception object.

## Installation

Just run in console:

```bash
composer req xepozz/stacktrace-parser --prefer-dist
```

## Usage

```php
use Xepozz\StacktraceParser\StacktraceParser;

$parser = new StacktraceParser();

$stacktrace = $parser->parse(<<<TEXT
Fatal error: Uncaught Exception in /in/hVvRE:5
Stack trace:
#0 /in/hVvRE(11): A->__g()
#1 {main}
  thrown in /in/hVvRE on line 5
TEXT
);

print_r($stacktrace);
```

Will be outputted:

```
Array
(
    [0] => Array
        (
            [file] => /in/hVvRE
            [line] => 11
            [class] => A
            [type] => ->
            [function] => __g
        )

)
```

If you want to see more examples look into [`tests`](tests/) folder

## Restriction

As a lot of custom tools this tool has one restriction. It's absent `args` property in trace item.

It's absent, because there is no way how to reproduce reduced value back to PHP-like value.

So parser even doesn't try to restore these values.
