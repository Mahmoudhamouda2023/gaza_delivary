<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Support;

use Illuminate\Validation\ValidationException;

final class PhoneNumber
{
    public static function normalize(string $input): string
    {
        $input = strtr(trim($input), array_combine(
            preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789')
        ));
        if (! preg_match('/^05[0-9]{8}$/D', $input)) {
            throw ValidationException::withMessages(['phone' => 'أدخل رقم جوال محلي من 10 خانات يبدأ بـ 05، دون مقدمة دولية.']);
        }

        return $input;
    }
}
