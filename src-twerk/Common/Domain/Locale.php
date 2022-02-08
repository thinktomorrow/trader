<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain;

use App\Shop\TraderConfig;

class Locale
{
    private string $language;
    private string $region;

    private function __construct(string $language, string $region)
    {
        $this->language = $language;
        $this->region = strtoupper($region);
    }

    public static function fromString(string $language, string $region): self
    {
        return new static($language, $region);
    }

    public static function default(): self
    {
        return static::fromString((new TraderConfig())->defaultLanguage(), (new TraderConfig())->defaultRegion());
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function toIsoCode(): string
    {
        return $this->language . '_' . $this->region;
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
