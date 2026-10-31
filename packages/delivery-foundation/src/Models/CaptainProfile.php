<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptainProfile extends Model
{
    protected $guarded = ['*'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
