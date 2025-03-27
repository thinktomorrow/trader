<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate;

enum VatNumberValidationState: string
{
    case valid = 'valid';
    case invalid = 'invalid';
    case service_error = 'service_error';
    case unknown = 'unknown';
}
