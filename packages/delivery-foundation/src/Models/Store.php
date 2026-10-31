<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Models;

use GazaDelivery\Foundation\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Store extends Model
{
    protected $guarded = ['*'];

    protected $casts = ['status' => StoreStatus::class, 'reviewed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (self $store) => $store->public_id ??= (string) Str::ulid());
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by_staff_id');
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_user_id', $user->getKey());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
