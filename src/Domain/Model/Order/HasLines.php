<?php

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

trait HasLines
{
    private array $lines = [];

    public function addOrUpdateLine(LineId $lineId, VariantId $productId, LinePrice $linePrice, Quantity $quantity): void
    {
        if (null !== $this->findLineIndex($lineId)) {
            $this->updateLine($lineId, $linePrice, $quantity);

            return;
        }

        $this->addLine($lineId, $productId, $linePrice, $quantity);
    }

    private function addLine(LineId $lineId, VariantId $productId, LinePrice $linePrice, Quantity $quantity): void
    {
        $this->lines[] = Line::create($this->orderId, $lineId, $productId, $linePrice, $quantity);

        $this->recordEvent(new LineAdded($this->orderId, $lineId, $productId));
    }

    private function updateLine(LineId $lineId, LinePrice $linePrice, Quantity $quantity): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->updatePrice($linePrice);
            $this->lines[$lineIndexToBeUpdated]->updateQuantity($quantity);

            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function updateLinePrice(LineId $lineId, LinePrice $linePrice): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->updatePrice($linePrice);

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

    public function getNextLineId(): LineId
    {
        $i = mt_rand(1,999);
        $nextLineId = LineId::fromString(substr($i .'_' . $this->orderId->get(), 0, 36));

        while(null !== $this->findLineIndex($nextLineId)) {
            $nextLineId = LineId::fromString(substr(++$i .'_' . $this->orderId->get(), 0, 36));
        }

        return $nextLineId;
    }
}
