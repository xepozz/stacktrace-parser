<?php
declare(strict_types=1);

function err()
{
    $exception = new Exception("test
message");
    var_dump($exception->getTrace());

    throw $exception;
}

try {
    err();
}catch (\Throwable $e){
    var_dump($e->getTrace());
}