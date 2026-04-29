<?php

namespace Portknock\Model;

readonly class HttpHeaders
{
    public const string AUTH_HEADER = 'HTTP_X_SESAM';
    public const string REMOTEADDR_HEADER = 'REMOTE_ADDR';

    public function __construct(private array $headers) {}

    public function getRemoteAddr(): ?string
    {
        return $this->headers[self::REMOTEADDR_HEADER] ?? null;
    }

    public function getSesamHeader(): ?string
    {
        return $this->headers[self::AUTH_HEADER] ?? null;
    }


}
