<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\PlatformFeeService;

class OrderObserver
{
    public function created(Order $order): void
    {
        app(PlatformFeeService::class)->processOrder($order);
    }

    public function updated(Order $order): void
    {
        if (!$order->wasChanged('status') && !$order->wasChanged('payment_status')) {
            return;
        }

        app(PlatformFeeService::class)->processOrder($order);
    }
}
