<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Exceptions\InvalidRequestException;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        return app('alipay')->web([
            'out_trade_no'      => $order->no,
            // 'total_amount'      => $order->total_amount,
            'total_amount'      => 1,
            'subject'           => '支付 Laravel Shop 的订单：' . $order->no,
        ]);
    }    

    // 前端回调页面
    public function alipayReturn()
    {
        // 校验提交的参数是否合法
        try {
            app('alipay')->verify();
        } catch(\Exception $e) {
            return view('pages.error', ['msg'=>'数据不正确']);
        }

        return view('pages.success', ['msg'=>'付款成功']);

    }

    // 服务器端回调
    public function alipayNotify()
    {
        // 校验输入参数
        $data = app('alipay')->verify();
        
        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        // 所有交易状态：https://docs.open.alipay.com/59/103672
        if (! in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        $order = Order::where('no', $data->out_trade_no)->first();
        if (! $order) {
            return 'fail';
        }

        if ($order->paid_at) {
            return app('alipay')->success();
        }

        $order->update([
            'paid_at'           => Carbon::now(),
            'payment_method'    => 'alipay',
            'payment_no'        => $data->trade_no, // 支付宝订单号
        ]);


        return app('alipay')->success();

    }
}
