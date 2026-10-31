<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Services;

use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

final class StoreDirectory
{
    // Use the same scoped builder for list, search, export and later Yajra endpoints.
    public function queryFor(Staff|User $actor): Builder
    {
        Gate::forUser($actor)->authorize('viewAny', Store::class);
        $query = Store::query();

        return $actor instanceof User ? $query->ownedBy($actor) : $query;
    }

    public function findFor(Staff|User $actor, string $publicId): Store
    {
        return $this->queryFor($actor)->where('public_id', $publicId)->firstOrFail();
    }
}
