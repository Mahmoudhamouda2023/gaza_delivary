<?php

namespace App\Models;

final class Expense extends DeliveryModel
{
    protected $casts = ['spent_on' => 'date'];
}
