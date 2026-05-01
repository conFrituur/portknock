<?php

namespace Portknock\Model;

readonly class HttpHeaders
{
    public const string HEADER_SESAM = 'HTTP_X_SESAM';
    public const string HEADER_REMOTE_ADDR = 'REMOTE_ADDR';

    public function __construct(private array $headers) {}

    public function getRemoteAddr(): ?string
    {
        return $this->headers[self::HEADER_REMOTE_ADDR] ?? null;
    }

    public function getSesam(): ?string
    {
        return $this->headers[self::HEADER_SESAM] ?? null;
    }
}
