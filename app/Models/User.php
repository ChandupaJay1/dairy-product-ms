<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SALES_REP = 'sales_rep';
    public const ROLE_DRIVER = 'driver';
    public const ROLE_CASHIER = 'cashier';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? 'admin') === self::ROLE_ADMIN;
    }

    public function isSalesRep(): bool
    {
        return ($this->role ?? '') === self::ROLE_SALES_REP;
    }

    public function isDriver(): bool
    {
        return ($this->role ?? '') === self::ROLE_DRIVER;
    }

    public function isCashier(): bool
    {
        return ($this->role ?? '') === self::ROLE_CASHIER;
    }

    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'System Administrator',
            self::ROLE_SALES_REP => 'Field Sales Representative',
            self::ROLE_DRIVER => 'Fleet Driver',
            self::ROLE_CASHIER => 'Settlement Officer',
            default => 'User'
        };
    }
}
