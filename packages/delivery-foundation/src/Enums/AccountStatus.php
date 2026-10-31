<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Enums;

enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
}
