<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'payment_method',
        'status',
        'amount',
        'response_data'
    ];

    protected $casts = [
        'response_data' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
