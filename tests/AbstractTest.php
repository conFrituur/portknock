<?php

namespace Portknock\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    protected const string SESAM_TOKEN = "SesamOpenU";
    protected const string REMOTE_ADDR = "2a02::e244";
    protected const string TEST_USER = "Test";

    protected function getTestHeaders(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/Fixtures/headers.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
