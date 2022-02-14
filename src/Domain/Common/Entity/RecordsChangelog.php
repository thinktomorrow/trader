<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Entity;

trait RecordsChangelog
{
    private int $changelogVersion = 0;

    private array $changelog = [];

    public function incrementChangelogVersion(): void
    {
        $this->changelogVersion++;
    }

    public function recordInChangelog(string $versionNote): void
    {
        if(!isset($this->changelog[$this->changelogVersion])) {
            $this->changelog[$this->changelogVersion] = [];
        }

        $this->changelog[$this->changelogVersion][] = $versionNote;
    }

    public function getPreviousChangelog(): array
    {
        if(!isset($this->changelog[$this->changelogVersion - 1])) {
            return [];
        }

        return $this->changelog[$this->changelogVersion - 1];
    }
}
