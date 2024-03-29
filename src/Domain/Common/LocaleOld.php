<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common;

use Assert\Assertion;

class LocaleOld
{
    private string $language;
    private string $region;

    private function __construct(string $language, string $region)
    {
        Assertion::minLength($language, 2);
        Assertion::minLength($region, 2);

        $this->language = $language;
        $this->region = strtolower($region);
    }

    public static function make(string $language, string $region): self
    {
        return new static($language, $region);
    }

    /**
     * From string e.g. nl-be, nl_BE or nl
     *
     * @param string $iso15897String
     * @return $this
     */
    public static function fromString(string $iso15897String): self
    {
        if (strpos($iso15897String, '-')) {
            return static::make(...explode('-', $iso15897String));
        }

        if (strpos($iso15897String, '_')) {
            return static::make(...explode('_', $iso15897String));
        }

        return static::make($iso15897String, $iso15897String);
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
     * To RFC 1766 standard which consists of the:
     * - ISO standard 639 language code
     * - uppercased ISO 3166-1 country/region codes
     *
     * https://en.wikipedia.org/wiki/ISO/IEC_15897
     * @return string
     */
    public function toIso15897(): string
    {
        return $this->language . '_' . strtoupper($this->region);
    }

    /**
     * The language (in ISO 639-1 format) and optionally a region (in ISO 3166-1 Alpha 2 format)
     * @return string
     */
    public function toIso639(): string
    {
        if ($this->language == $this->region) {
            return $this->language;
        }

        return $this->language . '-' . strtoupper($this->region);
    }

    public function __toString(): string
    {
        return $this->toIso15897();
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }
}
