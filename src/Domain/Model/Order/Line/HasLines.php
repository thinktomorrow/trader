<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LinePriceUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

trait HasLines
{
    /** @var Line[] */
    private array $lines = [];

    /** @return Line[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getQuantity(): Quantity
    {
        return Quantity::fromInt(
            array_reduce(
                $this->lines,
                fn ($carry, Line $line) => $carry + $line->getQuantity()->asInt(),
                0
            ),
        );
    }

    public function findLine(LineId $lineId): Line
    {
        if (null === ($lineIndexToBeUpdated = $this->findLineIndex($lineId))) {
            throw new \DomainException('No line found by id ' . $lineId->get() . ' on order ' . $this->orderId->get());
        }

        return $this->lines[$lineIndexToBeUpdated];
    }

    public function addOrUpdateLine(LineId $lineId, VariantId $productId, LinePrice $linePrice, Quantity $quantity, array $data): void
    {
        if (null !== $this->findLineIndex($lineId)) {
            $this->updateLine($lineId, $linePrice, $quantity, $data);

            return;
        }

        $this->addLine($lineId, $productId, $linePrice, $quantity, $data);
    }

    public function recordLineUpdatedEvent(LineId $lineId): void
    {
        $this->recordEvent(new LineUpdated($this->orderId, $lineId));
    }

    private function addLine(LineId $lineId, VariantId $variantId, LinePrice $linePrice, Quantity $quantity, array $data): void
    {
        $this->lines[] = Line::create($this->orderId, $lineId, $variantId, $linePrice, $quantity, $data);

        $this->recordEvent(new LineAdded($this->orderId, $lineId, $variantId));
    }

    private function updateLine(LineId $lineId, LinePrice $linePrice, Quantity $quantity, $data): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->updatePrice($linePrice);
            $this->lines[$lineIndexToBeUpdated]->updateQuantity($quantity);
            $this->lines[$lineIndexToBeUpdated]->addData($data);

            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function updateLinePrice(LineId $lineId, LinePrice $linePrice): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {

            $formerPrice = $this->lines[$lineIndexToBeUpdated]->getLinePrice();

            $this->lines[$lineIndexToBeUpdated]->updatePrice($linePrice);
            $this->recordEvent(new LinePriceUpdated($this->orderId, $lineId, $formerPrice, $linePrice));
            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function updateLineQuantity(LineId $lineId, Quantity $quantity): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->updateQuantity($quantity);

            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function updateLinePersonalisations(LineId $lineId, array $personalisations): void
    {
        if (null === $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            return;
        }

        $line = $this->lines[$lineIndexToBeUpdated];

        /** @var LinePersonalisation $personalisation */
        foreach ($personalisations as $personalisation) {
            $line->deletePersonalisation($personalisation->linePersonalisationId);
            $line->addPersonalisation($personalisation);
        }

        $this->recordEvent(new LineUpdated($this->orderId, $lineId));
    }

    public function updateLineData(LineId $lineId, array $data): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->addData($data);

            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function deleteLine(LineId $lineId): void
    {
        if (null !== $lineIndexToBeDeleted = $this->findLineIndex($lineId)) {
            $lineToBeDeleted = $this->lines[$lineIndexToBeDeleted];

            unset($this->lines[$lineIndexToBeDeleted]);

            $this->recordEvent(new LineDeleted($this->orderId, $lineToBeDeleted->lineId, $lineToBeDeleted->getVariantId()));
        }
    }

    private function findLineIndex(LineId $lineId): ?int
    {
        foreach ($this->lines as $index => $line) {
            if ($lineId->get() === $line->lineId->get()) {
                return $index;
            }
        }

        return null;
    }
}
