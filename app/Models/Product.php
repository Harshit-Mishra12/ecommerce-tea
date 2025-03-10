<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'images',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array', // Convert JSON images to array automatically
        'is_active' => 'boolean',
    ];

    public function wishlists()
{
    return $this->hasMany(Wishlist::class);
}

}
