<?php

namespace Thinktomorrow\Trader\Domain\Model\VatNumber;

interface HasVatNumber
{
    public function getVatNumber(): ?string;

    public function getVatNumberCountry(): ?string;

    public function isVatNumberValid(): bool;

    public function getVatNumberState(): VatNumberValidationState;
}
