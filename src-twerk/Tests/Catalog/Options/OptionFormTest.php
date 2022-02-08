<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog\Options;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Options\Application\OptionForm;
use Thinktomorrow\Trader\Catalog\Options\Application\SaveOptionsPayload;
use Thinktomorrow\Trader\Catalog\Options\Domain\OptionRepository;

class OptionFormTest extends TestCase
{
    use DatabaseMigrations;
    use OptionTestHelpers;

    /** @test */
    public function it_can_compose_values_for_form_repeat_field()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $output = app(OptionForm::class)->composeFormValues(
            app(OptionRepository::class)->get($productGroup->getId())
        );

        $this->assertCount(2, $output);
        $this->assertEquals([
            "type" => "1",
            "values" => [
                [
                    "id" => "1",
                    "value" => [
                        "nl" => "blauw",
                        "en" => "blue",
                    ],
                ],
                [
                    "id" => "2",
                    "value" => [
                        "nl" => "groen",
                        "en" => "green",
                    ],
                ],
            ],
        ], $output[0]);
    }

    /** @test */
    public function it_can_compose_form_values_to_payload_format()
    {
        $productGroup = $this->createProductGroup();

        $output = app(OptionForm::class)->composePayload($productGroup->getId(), [[
            "type" => "1",
            "values" => [
                [
                    "id" => "1",
                    "value" => [
                        "nl" => "blauw",
                        "en" => "blue",
                    ],
                ],
                [
                    "id" => "2",
                    "value" => [
                        "nl" => "groen",
                        "en" => "green",
                    ],
                ],
            ],
        ]]);

        $this->assertInstanceOf(SaveOptionsPayload::class, $output);
        $this->assertCount(2, $output->getEntries());
    }
}
