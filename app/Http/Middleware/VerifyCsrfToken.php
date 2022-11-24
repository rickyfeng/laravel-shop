<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     *  CSRF Token 白名单
     *
     * @var array<int, string>
     */
    protected $except = [
        'payment/alipay/notify',
    ];
}
