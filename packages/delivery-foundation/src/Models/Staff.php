<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Models;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class Staff extends Authenticatable
{
    use HasRoles;

    protected $table = 'staff';

    protected $guard_name = 'staff';

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
        static::creating(fn (self $staff) => $staff->public_id ??= (string) Str::ulid());
    }

    public function setPhoneAttribute(string $value): void
    {
        $this->attributes['phone'] = PhoneNumber::normalize($value);
    }

    public function captainProfile(): HasOne
    {
        return $this->hasOne(CaptainProfile::class, 'staff_id');
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
