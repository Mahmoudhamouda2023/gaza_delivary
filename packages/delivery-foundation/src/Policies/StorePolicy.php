<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Policies;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;

final class StorePolicy
{
    public function viewAny(Staff|User $actor): bool
    {
        return $actor instanceof Staff
            ? $actor->isActive() && $actor->hasPermissionTo('stores.view', 'staff')
            : $actor->status !== AccountStatus::Suspended;
    }

    public function view(Staff|User $actor, Store $store): bool
    {
        if ($actor instanceof Staff) {
            return $this->viewAny($actor);
        }

        return $actor->status !== AccountStatus::Suspended
            && (int) $store->owner_user_id === (int) $actor->getKey();
    }

    public function review(Staff|User $actor, Store $store): bool
    {
        return $actor instanceof Staff
            && $actor->isActive()
            && $actor->hasPermissionTo('stores.review', 'staff');
    }
}
