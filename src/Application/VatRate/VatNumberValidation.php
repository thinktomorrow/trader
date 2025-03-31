<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\InvalidVatNumber;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\VatNumberCountryMismatch;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatNumberValidationState;

class VatNumberValidation
{
    public function __construct(
        public readonly string                   $countryCode,
        public readonly string                   $vatNumber,
        public readonly VatNumberValidationState $state,
        public readonly array                    $data
    ) {
    }

    public static function fromException(string $countryCode, string $vatNumber, \Exception $exception): self
    {
        return new self($countryCode, $vatNumber, VatNumberValidationState::invalid, ['error' => $exception->getMessage()]);
    }

    public static function fromInvalidVatFormat(string $countryCode, string $vatNumber, InvalidVatNumber $exception): self
    {
        return new self($countryCode, $vatNumber, VatNumberValidationState::invalid_format, ['error' => $exception->getMessage()]);
    }

    public static function fromVatNumberCountryMismatch(string $countryCode, string $vatNumber, VatNumberCountryMismatch $exception): self
    {
        return new self($countryCode, $vatNumber, VatNumberValidationState::country_mismatch, ['error' => $exception->getMessage()]);
    }

    public function isValid(): bool
    {
        return in_array($this->state, [VatNumberValidationState::valid]);
    }

    public function getError(): ?string
    {
        return $this->data['error'] ?? null;
    }
}
