<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'tax',
        'shipping',
        'total',
        'status',
        'payment_status',
        'shipping_address',
        'billing_address',
        'razorpay_order_id',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Generating a unique order number
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $dateCode = date('Ymd');
        $randomCode = mt_rand(1000, 9999);
        return $prefix . $dateCode . $randomCode;
    }
}
