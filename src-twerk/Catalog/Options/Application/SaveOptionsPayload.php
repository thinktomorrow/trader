<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Application;

class SaveOptionsPayload
{
    private string $productGroupId;
    private array $entries = [];

    public function __construct(string $productGroupId)
    {
        $this->productGroupId = $productGroupId;
    }

    public function add(string $optionTypeId, array $translations, ?string $id = null): self
    {
        $this->entries[] = [
            'option_type_id' => $optionTypeId,
            'translations' => $translations,
            'id' => $id,
        ];

        return $this;
    }

    public function removeEmptyTranslations(): self
    {
        foreach ($this->entries as $k => $entry) {

            // Remove empty values
            foreach ($entry['translations'] as $i => $translation) {
                if (null === $translation) {
                    unset($this->entries[$k]['translations'][$i]);
                }
            }
        }

        return $this;
    }

    public function getProductGroupId(): string
    {
        return $this->productGroupId;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }
}
