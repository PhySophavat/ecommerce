<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\PlatformFeeService;

class OrderItemObserver
{
    public function created(OrderItem $orderItem): void
    {
        $this->processOrder($orderItem);
    }

    public function updated(OrderItem $orderItem): void
    {
        if (!$orderItem->wasChanged(['product_id', 'line_total', 'quantity'])) {
            return;
        }

        $this->processOrder($orderItem);
    }

    private function processOrder(OrderItem $orderItem): void
    {
        if (!$orderItem->order) {
            return;
        }

        app(PlatformFeeService::class)->processOrder($orderItem->order);
    }
}
