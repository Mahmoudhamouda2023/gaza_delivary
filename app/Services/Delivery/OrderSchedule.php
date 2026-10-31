<?php

namespace App\Services\Delivery;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class OrderSchedule
{
    public static function attributes(array $input): array
    {
        $input['delivery_mode'] ??= 'now';
        $d = Validator::make($input, [
            'delivery_mode' => ['required', Rule::in(['now', 'scheduled'])],
            'scheduled_for' => ['exclude_unless:delivery_mode,scheduled', 'required', 'date_format:Y-m-d\TH:i'],
        ])->validate();
        $when = ($d['delivery_mode'] === 'scheduled') ? Carbon::createFromFormat('Y-m-d\TH:i', $d['scheduled_for'], 'Asia/Hebron')->startOfMinute()->utc() : null;
        if ($when && $when->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['scheduled_for' => 'اختر موعدًا في المستقبل بتوقيت فلسطين.']);
        }

        return ['delivery_mode' => $d['delivery_mode'], 'scheduled_for' => $when];
    }
}
