<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'total_amount',
        'status',
        'shipment_details',
    ];

    protected $casts = [
        'shipment_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
