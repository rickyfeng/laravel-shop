<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Models\Order;
use Carbon\Carbon;
use App\Jobs\CloseOrder;
use App\Services\OrderService;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\SendReviewRequest;
use App\Events\OrderReviewed;
class OrdersController extends Controller
{
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user    = $request->user();
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'));
    }

    public function index(Request $request)
    {
        $orders = Order::query()
                    ->with(['items.product','items.productSku'])
                    ->where('user_id', $request->user()->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate();

        return view('orders.index', ['orders'=>$orders]);

    }

    public function show(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        return view('orders.show', ['order'=> $order->load(['items.productSku', 'items.product'])]);
    }

    public function received(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        
        // 判断订单的发货状态是否为已发货
        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('发货状态不正确');
        }

        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        return $order;
    }

    public function review(Order $order)
    {
        $this->authorize('own', $order);
        // 判断是否已经支付
        if (! $order->paid_at) {
            throw new InvalidRequestException('该订单未支付， 不可以评价');
        }

        return view('orders.review', ['order'=>$order->load('items.productSku', 'items.product')]);
    }

    public function sendReview(Order $order, SendReviewRequest $request)
    {
        $this->authorize('own', $order);

        if (! $order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可以评价');
        }
        // 判断是否已经评价
        if ($order->reviewed) {
            throw new InvalidRequestException('该订单已评价，不可重复提交');
        }

        $reviews = $request->input('reviews');

        \DB::transaction(function() use ($reviews, $order){
            foreach($reviews as $review) {
                $orderItem = $order->items()->find($review['id']);

                $orderItem->update([
                    'rating'        => $review['rating'],
                    'review'        => $review['review'],
                    'reviewed_at'   => Carbon::now(),
                ]);
            }

            $order->update(['reviewed'=>true]);
        });

        event(new OrderReviewed($order));

        return redirect()->back();
    }
}