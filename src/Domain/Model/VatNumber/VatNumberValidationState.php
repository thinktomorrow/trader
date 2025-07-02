<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatNumber;

enum VatNumberValidationState: string
{
    case valid = 'valid';
    case invalid = 'invalid';
    case invalid_format = 'invalid_format';
    case country_mismatch = 'country_mismatch';
    case service_error = 'service_error';
    case unknown = 'unknown';
}
