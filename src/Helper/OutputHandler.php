<?php

namespace Portknock\Helper;

class OutputHandler
{
    public function die(int $code): never
    {
        http_response_code($code);
        die("{$code} NEIN!");
    }

    public function echo(string $output): void
    {
        echo $output;
    }
}
