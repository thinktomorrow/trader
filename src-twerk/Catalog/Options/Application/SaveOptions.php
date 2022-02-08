<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Application;

use Thinktomorrow\Trader\Catalog\Options\Domain\Option;
use Thinktomorrow\Trader\Catalog\Options\Domain\OptionRepository;

class SaveOptions
{
    private OptionRepository $productGroupOptionRepository;

    public function __construct(OptionRepository $productGroupOptionRepository)
    {
        $this->productGroupOptionRepository = $productGroupOptionRepository;
    }

    public function handle(SaveOptionsPayload $payload): void
    {
        $existingValues = $this->productGroupOptionRepository->get($payload->getProductGroupId());

        // DELETE existing option values that are no longer present in the payload.
        /** @var Option $existingValue */
        foreach ($existingValues as $existingValue) {
            if (! $this->isPresentInPayload($existingValue->getId(), $payload->getEntries())) {
                $this->productGroupOptionRepository->delete($existingValue->getId());
            }
        }

        // Update or create options
        foreach ($payload->getEntries() as $entry) {
            if ($entry['id']) {
                $this->productGroupOptionRepository->save((string)$entry['id'], [
                    'values' => $entry['translations'],
                ]);
            } else {
                $this->productGroupOptionRepository->create([
                    'option_type_id' => $entry['option_type_id'],
                    'productgroup_id' => $payload->getProductGroupId(),
                    'values' => $entry['translations'],
                ]);
            }
        }
    }

    private function isPresentInPayload(string $id, array $entries): bool
    {
        foreach ($entries as $entry) {
            if ($entry['id'] && $entry['id'] == $id) {
                return true;
            }
        }

        return false;
    }
}
