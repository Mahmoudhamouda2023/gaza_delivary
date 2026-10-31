<?php

use GazaDelivery\Foundation\Models\Staff;
use GazaDelivery\Foundation\Models\User;

return [
    'defaults' => ['guard' => 'merchant'],
    'guards' => [
        'staff' => ['driver' => 'session', 'provider' => 'delivery_staff'],
        'merchant' => ['driver' => 'session', 'provider' => 'delivery_merchants'],
    ],
    'providers' => [
        'delivery_staff' => ['driver' => 'eloquent', 'model' => Staff::class],
        'delivery_merchants' => ['driver' => 'eloquent', 'model' => User::class],
    ],
    'passwords' => [],
    'password_timeout' => 10800,
];
