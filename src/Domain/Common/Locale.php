<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common;

final class Locale
{
    private string $language;
    private string $region;

    private function __construct(string $language, string $region)
    {
        $this->language = $language;
        $this->region = strtolower($region);
    }

    public static function fromString(string $language, string $region): self
    {
        return new static($language, $region);
    }

    /**
     * From ISO 639-1 standard
     *
     * @param string $isoCode
     * @return $this
     */
    public static function fromIsoCode(string $isoCode): self
    {
        if(!strpos($isoCode, '-')) {
            throw new \InvalidArgumentException('Invalid isocode format: ' . $isoCode);
        }

        return static::fromString(...explode('-', $isoCode));
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * ISO 639-1 standard
     * @return string
     */
    public function toIsoCode(): string
    {
        return $this->language . '-' . $this->region;
    }

    public function __toString(): string
    {
        return $this->language;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }
}

