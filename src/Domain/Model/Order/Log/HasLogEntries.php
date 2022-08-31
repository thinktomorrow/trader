<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Log;

trait HasLogEntries
{
    /** @var LogEntry[] */
    private array $logEntries = [];

    public function addLogEntry(LogEntry $logEntry): void
    {
        $this->logEntries[] = $logEntry;
    }

    public function getLogEntries(): array
    {
        return $this->logEntries;
    }
}
