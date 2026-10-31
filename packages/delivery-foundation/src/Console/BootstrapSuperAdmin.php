<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Console;

use GazaDelivery\Foundation\Enums\AccountStatus;
use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Services\AuditRecorder;
use GazaDelivery\Foundation\Support\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

final class BootstrapSuperAdmin extends Command
{
    protected $signature = 'delivery:bootstrap-admin';

    protected $description = 'Create the first super administrator using hidden password prompts; refuses subsequent creation.';

    public function handle(AuditRecorder $audit): int
    {
        if (Staff::query()->role('super_admin', 'staff')->exists()) {
            $this->error('A super administrator already exists. Use the authorized staff management service.');

            return self::FAILURE;
        }

        $input = [
            'name' => $this->ask('اسم المشرف العام'),
            'phone' => PhoneNumber::normalize((string) $this->ask('رقم الجوال')),
            'password' => $this->secret('كلمة المرور: 8 خانات على الأقل، تتضمن أحرفًا وأرقامًا'),
            'password_confirmation' => $this->secret('تأكيد كلمة المرور'),
        ];
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'unique:staff,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ])->validate();

        $created = DB::transaction(function () use ($data, $audit): bool {
            Role::query()->where('name', 'super_admin')->where('guard_name', 'staff')->lockForUpdate()->firstOrFail();
            if (Staff::query()->role('super_admin', 'staff')->exists()) {
                return false;
            }

            $staff = new Staff;
            $staff->forceFill([
                'name' => trim($data['name']), 'phone' => $data['phone'],
                'password' => $data['password'], 'status' => AccountStatus::Active,
                'must_change_password' => false,
            ])->save();
            $staff->assignRole('super_admin');
            $audit->record('staff.bootstrapped', $staff, null, ['roles' => ['super_admin']]);

            return true;
        });

        $created ? $this->info('تم إنشاء المشرف العام.') : $this->error('Another process already created a super administrator.');

        return $created ? self::SUCCESS : self::FAILURE;
    }
}
