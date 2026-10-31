<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AccountAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit records are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit records are append-only.'));
    }
}
