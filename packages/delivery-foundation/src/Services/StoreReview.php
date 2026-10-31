<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Services;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Enums\StoreStatus;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class StoreReview
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function transition(Staff $actor, Store $store, StoreStatus $target, string $reason): Store
    {
        Validator::make(['reason' => trim($reason)], ['reason' => ['required', 'string', 'min:3', 'max:500']])->validate();

        return DB::transaction(function () use ($actor, $store, $target, $reason): Store {
            // Recheck the actor inside the write transaction; never trust request-supplied status.
            $actor = Staff::query()->lockForUpdate()->findOrFail($actor->getKey());
            $store = Store::query()->lockForUpdate()->findOrFail($store->getKey());
            Gate::forUser($actor)->authorize('review', $store);
            $owner = User::query()->lockForUpdate()->findOrFail($store->owner_user_id);
            $from = $store->status;

            $allowed = match ($from) {
                StoreStatus::Pending => [StoreStatus::Active, StoreStatus::Rejected],
                StoreStatus::Active => [StoreStatus::Suspended],
                StoreStatus::Suspended => [StoreStatus::Active],
                StoreStatus::Rejected => [StoreStatus::Pending],
            };

            if (! in_array($target, $allowed, true)) {
                throw ValidationException::withMessages(['status' => 'هذا الانتقال غير مسموح من حالة المتجر الحالية.']);
            }

            $store->forceFill([
                'status' => $target,
                'reviewed_by_staff_id' => $actor->getKey(),
                'reviewed_at' => now(),
                'review_reason' => trim($reason),
            ])->save();

            $owner->forceFill([
                'status' => match ($target) {
                    StoreStatus::Active => AccountStatus::Active,
                    StoreStatus::Suspended => AccountStatus::Suspended,
                    default => AccountStatus::Pending,
                },
                'session_version' => $owner->session_version + 1,
                'remember_token' => null,
            ])->save();

            $this->audit->record('store.status_changed', $store, $actor, [
                'from' => $from->value, 'to' => $target->value, 'reason' => trim($reason),
            ]);

            return $store->refresh();
        });
    }
}
