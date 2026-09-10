<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Vat\Exceptions;

final class InvalidVatAllocatedTotal extends \InvalidArgumentException
{
    public function __construct(
        private readonly InvalidVatAllocatedTotalReason $reason,
        string $message,
        private readonly array $diagnosticContext = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function reason(): InvalidVatAllocatedTotalReason
    {
        return $this->reason;
    }

    /**
     * Laravel and Sentry consume this method without coupling the domain to either framework.
     */
    public function context(): array
    {
        return [
            ...$this->diagnosticContext,
            'reason' => $this->reason->value,
        ];
    }

    public function withContext(array $context): self
    {
        return new self(
            $this->reason,
            $this->getMessage(),
            array_replace($context, $this->diagnosticContext),
            $this,
        );
    }
}
