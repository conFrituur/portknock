<?php

namespace Portknock\Model;

use Portknock\Helper\Log;
use RuntimeException;

readonly class HttpHeaders
{
    public const string HEADER_SESAM = 'HTTP_X_SESAM';
    public const string HEADER_REMOTE_ADDR = 'REMOTE_ADDR';
    public const string HEADER_REQUEST_URI = 'REQUEST_URI';
    public const string HEADER_PHP_SELF = 'PHP_SELF';

    public function __construct(private array $headers) {}

    public function getRemoteAddr(): ?string
    {
        return $this->headers[self::HEADER_REMOTE_ADDR] ?? null;
    }

    public function getSesam(): ?string
    {
        return $this->headers[self::HEADER_SESAM] ?? null;
    }

    public function getAll(): array
    {
        return $this->headers;
    }

    public function getRoutingUri(): string
    {
        // contains the called URL from the client
        $requestUri = $this->headers[self::HEADER_REQUEST_URI] ?? null;
        // contains the uri of the index.php > /app/test/index.php, assuming this is called from public/index.php
        $phpFileUri = $this->headers[self::HEADER_PHP_SELF] ?? null;

        if (!$requestUri || !$phpFileUri) {
            throw new RuntimeException("HttpHeader is missing " . self::HEADER_REQUEST_URI . ' or ' . self::HEADER_PHP_SELF);
        }
        $uri = parse_url($requestUri, PHP_URL_PATH);
        if (!$uri) {
            throw new RuntimeException(self::HEADER_REQUEST_URI . " is not a valid URI[={$requestUri}]");
        }

        // if request_uri matches the php file (e.g. index.php) route to same as / would have
        if ($uri === $phpFileUri) {
            return '';
        }

        // strip the php filename of the uri, so we get the "base path"
        $path = dirname($phpFileUri);

        // sanity check
        if (!str_starts_with($uri, $path)) {
            throw new RuntimeException("Uri[=$uri] should start with[={$path}]");
        }

        // trim the basepath from URI to get the called routable URI
        $routableUri = str_replace($path, '', $uri);

        // remove all slashes for more consistent output
        return str_replace('/', '', $routableUri);
    }
}
