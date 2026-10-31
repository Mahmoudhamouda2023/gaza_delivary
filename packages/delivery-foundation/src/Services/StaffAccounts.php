<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Services;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Enums\StaffRole;
use GazaDelivery\Foundation\Models\CaptainProfile;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class StaffAccounts
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function provision(Staff $actor, array $input): Staff
    {
        Validator::make($input, ['phone' => ['required', 'string', 'max:40']])->validate();
        $input['phone'] = PhoneNumber::normalize($input['phone']);
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'unique:staff,phone'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'distinct', new Enum(StaffRole::class)],
            'vehicle_type' => ['required_if:roles.*,captain', 'nullable', 'in:bicycle,electric_bicycle'],
        ])->validate();

        if (in_array('captain', $data['roles'], true) && empty($data['vehicle_type'])) {
            throw ValidationException::withMessages(['vehicle_type' => 'حدد وسيلة توصيل الكابتن.']);
        }

        return DB::transaction(function () use ($actor, $data): Staff {
            $this->lockSuperAdminRole();
            $actor = $this->authorizedActor($actor);
            $staff = new Staff;
            $staff->forceFill([
                'name' => trim($data['name']), 'phone' => $data['phone'],
                'email' => $data['email'] ?? null, 'password' => $data['password'],
                'status' => AccountStatus::Active, 'must_change_password' => true,
            ])->save();
            $staff->syncRoles($data['roles']);

            if (in_array('captain', $data['roles'], true)) {
                $profile = new CaptainProfile;
                $profile->forceFill(['staff_id' => $staff->getKey(), 'vehicle_type' => $data['vehicle_type']])->save();
            }

            $this->audit->record('staff.created', $staff, $actor, ['roles' => $data['roles']]);

            return $staff;
        });
    }

    public function suspend(Staff $actor, Staff $subject, string $reason): void
    {
        Validator::make(['reason' => trim($reason)], ['reason' => ['required', 'string', 'min:3', 'max:500']])->validate();

        DB::transaction(function () use ($actor, $subject, $reason): void {
            $this->lockSuperAdminRole();
            $actor = $this->authorizedActor($actor);
            $subject = Staff::query()->lockForUpdate()->findOrFail($subject->getKey());

            if ($subject->status === AccountStatus::Suspended) {
                return;
            }

            if ($subject->hasRole('super_admin', 'staff')
                && Staff::query()->where('status', 'active')->role('super_admin', 'staff')->count() <= 1) {
                throw ValidationException::withMessages(['staff' => 'لا يمكن إيقاف آخر مشرف عام فعّال.']);
            }

            $subject->forceFill([
                'status' => AccountStatus::Suspended,
                'session_version' => $subject->session_version + 1,
                'remember_token' => null,
            ])->save();
            $this->audit->record('staff.suspended', $subject, $actor, [
                'from' => 'active', 'to' => 'suspended', 'reason' => trim($reason),
            ]);
        });
    }

    private function lockSuperAdminRole(): void
    {
        // Serialize privileged mutations; protects the last active administrator on MySQL/PostgreSQL.
        Role::query()->where('name', 'super_admin')->where('guard_name', 'staff')->lockForUpdate()->firstOrFail();
    }

    private function authorizedActor(Staff $actor): Staff
    {
        $actor = Staff::query()->lockForUpdate()->findOrFail($actor->getKey());

        if (! $actor->isActive() || ! $actor->hasRole('super_admin', 'staff')) {
            throw new AuthorizationException('Only an active super administrator may manage staff accounts.');
        }

        return $actor;
    }
}
