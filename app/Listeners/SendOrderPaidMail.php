<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Notifications\OrderPaidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

// implements ShouldQueue 代表异步监听器
class SendOrderPaidMail implements ShouldQueue
{

    public function handle(OrderPaid $event)
    {
        $order = $event->getOrder();
        $order->user->notify(new OrderPaidNotification($order));
    }
}
