<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Enums;

enum StaffRole: string
{
    case SuperAdmin = 'super_admin';
    case Operations = 'operations';
    case Finance = 'finance';
    case Captain = 'captain';
}
