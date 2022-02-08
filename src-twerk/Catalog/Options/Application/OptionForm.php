<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Application;

use Thinktomorrow\Trader\Catalog\Options\Domain\Option;
use Thinktomorrow\Trader\Catalog\Options\Domain\Options;

class OptionForm
{
    public function composeFormValues(Options $options): array
    {
        $output = [];

        /** @var Option $option */
        foreach ($options as $option) {
            if (! isset($output[$option->getOptionTypeId()])) {
                $output[$option->getOptionTypeId()] = [
                    'type' => $option->getOptionTypeId(),
                    'values' => [],
                ];
            }

            $output[$option->getOptionTypeId()]['values'][] = [
                'id' => $option->getId(), 'value' => $option->getValues(),
            ];
        }

        return array_values($output);
    }

    public function composePayload(string $productGroupId, array $formValues): SaveOptionsPayload
    {
        $payload = new SaveOptionsPayload($productGroupId);

        foreach ($formValues as $formValue) {
            if (! isset($formValue['type'])) {
                continue;
            }

            foreach ($formValue['values'] as $value) {
                $payload->add(
                    (string) $formValue['type'],
                    $value['value'],
                    $value['id'] ?? null
                );
            }
        }

        $payload->removeEmptyTranslations();

        return $payload;
    }
}
