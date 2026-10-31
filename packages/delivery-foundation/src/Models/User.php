<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Models;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = ['*'];

    protected $hidden = ['password', 'remember_token', 'session_version'];

    protected $casts = [
        'password' => 'hashed',
        'status' => AccountStatus::class,
        'must_change_password' => 'boolean',
        'session_version' => 'integer',
        'last_login_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $user) => $user->public_id ??= (string) Str::ulid());
    }

    public function setPhoneAttribute(string $value): void
    {
        $this->attributes['phone'] = PhoneNumber::normalize($value);
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class, 'owner_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
