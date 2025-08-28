<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common;

use Assert\Assertion;

final class Locale
{
    private string $locale;

    private function __construct(string $locale)
    {
        Assertion::minLength($locale, 2);

        // Normalize the locale input
        $this->locale = strtolower(str_replace('_', '-', $locale));
    }


    public static function fromString(string $locale): self
    {
        return new static($locale);
    }

    public function get(): string
    {
        return $this->locale;
    }

    public function getLanguage(): string
    {
        [$language,] = $this->splitInLanguageAndRegion();

        return $language;
    }

    public function getRegion(): string
    {
        [, $region] = $this->splitInLanguageAndRegion();

        return $region;
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
        [$language, $region] = $this->splitInLanguageAndRegion();

        return $language . '_' . strtoupper($region);
    }

    /**
     * The language (in ISO 639-1 format) and optionally a region (in ISO 3166-1 Alpha 2 format)
     * @return string
     */
    public function toIso639(): string
    {
        [$language, $region] = $this->splitInLanguageAndRegion();

        if ($language == $region) {
            return $language;
        }

        return $language . '-' . strtoupper($region);
    }

    public function __toString(): string
    {
        return $this->get();
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }

    private function splitInLanguageAndRegion(): array
    {
        if (strpos($this->locale, '-')) {
            return explode('-', $this->locale);
        }

        if (strpos($this->locale, '_')) {
            return explode('_', $this->locale);
        }

        return [$this->locale, $this->locale];
    }
}
