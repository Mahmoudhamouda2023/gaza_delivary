<?php

declare(strict_types=1);

namespace GazaDelivery\Foundation\Enums;

enum StoreStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
}
