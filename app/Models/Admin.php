<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

class Admin extends Authenticatable
{
    use HasFactory;
    protected $fillable = ['name', 'email', 'password', 'role', 'mobile', 'allowed_resources'];

    protected $table = 'users'; // 👈 Use the "users" table instead of "admins"
    use HasFactory;
    use HasRoles, HasApiTokens;

    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'allowed_resources' => 'array',
    ];

    // public function canAccessFilament(): bool
    // {
    //     return in_array($this->role, ['admin', 'manager']); // Only allow admins/managers
    // }
    public function canAccessFilament(): bool
    {
        return $this->role === 'admin' || !empty($this->allowed_resources);
    }

    // public function canAccessFilament(): bool
    // {
    //     return $this->hasRole(['Admin', 'Manager']); // ✅ Correct role check
    // }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }
    public function allowedResources(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? json_decode($value, true) : [],
            set: fn($value) => json_encode($value),
        );
    }

    public function canAccessResource(string $resource): bool
    {
        if ($this->role === 'admin') {
            return true; // Admin can access everything
        }

        return in_array($resource, $this->allowedResources);
    }

    public function hasRole($role): bool
    {
        return $this->role === $role; // Ensure 'role' exists in your users table
    }
    public function getRoleFromDatabase(): ?string
    {
        return DB::table('users')
            ->where('id', $this->id)
            ->value('role'); // Assuming 'role' is a column in the 'users' table
    }
}
