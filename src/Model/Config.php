<?php

namespace Portknock\Model;

readonly class Config
{
    // these should only contain the hostname of the URL. the rest will be taken from the original request URI
    public const string FIELD_V4_REDIRECT_HOST = 'v4host';
    public const string FIELD_V6_REDIRECT_HOST = 'v6host';

    public function __construct(
        private ?string $v4RedirectHost = null,
        private ?string $v6RedirectHost = null,
    ) {}

    public function getV4RedirectHost(): ?string
    {
        return $this->v4RedirectHost;
    }

    public function getV6RedirectHost(): ?string
    {
        return $this->v6RedirectHost;
    }

    /**
     * @param string[] $jsonData
     * @return self
     */
    public static function fromJsonData(array $jsonData): self
    {
        return new self(
            $jsonData[self::FIELD_V4_REDIRECT_HOST] ?? null,
            $jsonData[self::FIELD_V6_REDIRECT_HOST] ?? null
        );
    }
}
