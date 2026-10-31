<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Services;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Enums\StoreStatus;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Models\User;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class MerchantRegistration
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function register(array $input): User
    {
        Validator::make($input, ['phone' => ['required', 'string', 'max:40']])->validate();
        $input['phone'] = PhoneNumber::normalize($input['phone']);

        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'store_name' => ['required', 'string', 'max:160'],
            'password' => ['required', 'string', 'confirmed', Password::min(config('delivery-foundation.password_min_length', 8))->letters()->numbers()],
        ])->validate();

        try {
            return DB::transaction(function () use ($data): User {
                $user = new User;
                $user->forceFill([
                    'name' => trim($data['name']),
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'password' => $data['password'],
                    'status' => AccountStatus::Pending,
                ])->save();

                $store = new Store;
                $store->forceFill([
                    'owner_user_id' => $user->getKey(),
                    'name' => trim($data['store_name']),
                    'status' => StoreStatus::Pending,
                ])->save();

                $this->audit->record('merchant.registered', $user, null);
                $this->audit->record('store.registered', $store, null);

                return $user->load('store');
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Handles simultaneous registrations after both requests passed validation.
            if (User::query()->where('phone', $data['phone'])->exists()) {
                throw ValidationException::withMessages(['phone' => 'رقم الجوال مستخدم بالفعل.']);
            }

            throw $exception;
        }
    }
}
