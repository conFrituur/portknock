<?php

namespace Portknock\Helper;

class OutputHandler
{
    public function die(int $code, string $message = "NEIN!"): never
    {
        http_response_code($code);
        die("{$code} {$message}");
    }

    public function redirect(string $url): never
    {
        http_response_code(307);
        header("Location: $url");
        die('307 Temporary Redirect');
    }

    public function echo(string $output): void
    {
        echo $output;
    }
}
