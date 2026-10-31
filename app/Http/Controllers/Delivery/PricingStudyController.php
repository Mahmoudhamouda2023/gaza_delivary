<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Services\Delivery\Money;
use Illuminate\Http\Request;

final class PricingStudyController extends Controller
{
    public function __invoke(Request $r)
    {
        $d = $r->validate([
            'fee' => ['nullable', 'regex:/^\d{1,5}(\.\d{1,2})?$/'],
            'captain_percent' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'],
            'variable_cost' => ['nullable', 'regex:/^\d{1,5}(\.\d{1,2})?$/'],
            'fixed_cost' => ['nullable', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'monthly_orders' => ['nullable', 'integer', 'between:1,1000000'],
        ]);
        $inputs = array_replace(['fee' => '10', 'captain_percent' => '70', 'variable_cost' => '0.50', 'fixed_cost' => '1000', 'monthly_orders' => '500'], array_filter($d, fn ($v) => $v !== null && $v !== ''));
        $fee = Money::minor($inputs['fee']);
        $captain = Money::percent($fee, Money::minor((string) $inputs['captain_percent']));
        $office = $fee - $captain;
        $contribution = $office - Money::minor($inputs['variable_cost']);
        $fixed = Money::minor($inputs['fixed_cost']);
        $profit = $contribution * (int) $inputs['monthly_orders'] - $fixed;
        $breakEven = $contribution > 0 ? (int) ceil($fixed / $contribution) : null;

        return view('delivery.pricing-study', compact('inputs', 'fee', 'captain', 'office', 'contribution', 'profit', 'breakEven'));
    }
}
