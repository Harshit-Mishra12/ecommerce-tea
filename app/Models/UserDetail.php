<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_image',
        'dob',
    ];

    // Accessor for Profile Image
    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image
            ? asset('storage/' . $this->profile_image)
            : asset('images/default-profile.png');
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
