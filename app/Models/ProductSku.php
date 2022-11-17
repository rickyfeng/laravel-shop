<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\InternalException;


class ProductSku extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'price', 'stock'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 减库存
    public function decreaseStock($amount)
    {
        if ($amount < 0) {
            throw new InternalException('减库存不可以小于0');
        }
        // 减库存不能简单地通过 update(['stock' => $sku->stock - $amount]) 来操作，
        // 在高并发的情况下会有问题，这就需要通过数据库的方式来解决。
        return $this->where('id', $this->id)->where('stock', '>=', $amount)->decrement('stock', $amount);
    }

    public function addStock($amount) 
    {
        if ($amount < 0) {
            throw new InternalException('加库存不可以小于0');
        }
        $this->increment('stock', $amount);
    }

}

