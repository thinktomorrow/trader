<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatNumber;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatNumber\Exceptions\InvalidVatNumber;
use Thinktomorrow\Trader\Domain\Model\VatNumber\Exceptions\VatNumberCountryMismatch;

class VatNumber
{
    private string $countryCode;

    private string $number;

    private function __construct(string $countryCode, string $number)
    {
        $this->validate($countryCode, $number);

        $this->countryCode = $countryCode;
        $this->number = self::split($countryCode, $number)[1];
    }

    //    public static function fromString(string $vatNumber): self
    //    {
    //        $vatNumber = self::cleanup($vatNumber);
    //
    //        $countryCode = substr($vatNumber, 0, 2);
    //        $number = substr($vatNumber, 2);
    //
    //        return new static($countryCode, $number);
    //    }

    public static function make(CountryId $countryCode, string $vatNumber): self
    {
        return new static($countryCode->get(), $vatNumber);
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function equals($other): bool
    {
        return ($other instanceof self && $other->countryCode === $this->countryCode && $other->number === $this->number);
    }

    public function get(): string
    {
        return $this->countryCode . $this->number;
    }

    public function __toString(): string
    {
        return $this->get();
    }

    private function validate(string $countryCode, string $number)
    {
        if (strlen($countryCode) !== 2 || ! ctype_alpha($countryCode)) {
            throw new InvalidVatNumber('Invalid country code [' . $countryCode . ']');
        }

        /**
         * Basic validation. A vat number consists usually of 9 or 10
         * but at least of eight characters + iso country code (2 chars)
         */
        if (! $number || strlen($number) < 8) {
            throw new InvalidVatNumber('Invalid vat number [' . $number . ']');
        }

        if (($includedCountryCode = self::findIncludedCountryCode($number)) && $includedCountryCode !== $countryCode) {
            throw new VatNumberCountryMismatch('Invalid vat number [' . $number . ']. Included country code [' . $includedCountryCode . '] does not match given country code [' . $countryCode . ']');
        }
    }

    private static function split(string $countryCode, string $vatNumber): array
    {
        $vatNumber = self::cleanup($vatNumber);

        if (self::findIncludedCountryCode($vatNumber)) {
            $vatNumber = substr($vatNumber, 2);
        }

        return [$countryCode, $vatNumber];
    }

    private static function cleanup($number): string
    {
        return str_replace([' ', '.', '-', ',', ', '], '', (string)$number);
    }

    private static function findIncludedCountryCode(string $number): ?string
    {
        if (strlen($number) < 2) {
            return null;
        }

        $includedCountryCode = substr($number, 0, 2);

        $matches = [];
        preg_match('/\A([A-Z]{2})/', $includedCountryCode, $matches);

        if (isset($matches[0]) && $matches[0]) {
            return $matches[0];
        }

        return null;
    }
}
