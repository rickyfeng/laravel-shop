<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

//  implements ShouldQueue 代表此监听器是异步执行的
class UpdateProductSoldCount implements ShouldQueue
{

    // Laravel 会默认执行监听器的 handle 方法，触发的事件会作为 handle 方法的参数
    public function handle(OrderPaid $event)
    {
        $order = $event->getOrder();
        // 预加载商品数据
        $order->load('items.product');
        foreach($order->items as $item) {
            $product = $item->product;

            $soldCount = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function($query){
                    $query->whereNoNull('paid_at');      // 关联的订单状态是已支付
                })->sum('amount');

            $product->update([
                'sold_count'    => $soldCount
            ]);
        }
    }
}
