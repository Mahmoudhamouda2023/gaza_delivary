<?php

namespace App\Services\Delivery;

use Illuminate\Validation\ValidationException;

final class Money
{
    public static function minor(string|int $value, string $field = 'amount'): int
    {
        $s = trim((string) $value);
        if (! preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D', $s)) {
            throw ValidationException::withMessages([$field => 'أدخل مبلغًا صحيحًا حتى منزلتين عشريتين.']);
        }
        [$whole,$part] = array_pad(explode('.', $s, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($part, 2, '0');
    }

    public static function show(int|string|null $minor): string
    {
        return number_format(((int) $minor) / 100, 2, '.', ',').' ₪';
    }

    public static function percent(int $amount, int $bps): int
    {
        return intdiv($amount * $bps + 5000, 10000);
    }
}
