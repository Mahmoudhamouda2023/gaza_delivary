@extends('layouts.shell')

@section('title', 'تفاصيل الطلب')

@section('content')
    @php
        $money = \App\Services\Delivery\Money::class;

        $allowed =
            [
                'created' => ['cancelled'],
                'assigned' => ['picked_up', 'cancelled'],
                'picked_up' => ['out_for_delivery', 'failed'],
                'out_for_delivery' => ['delivered', 'failed'],
                'failed' => ['out_for_delivery', 'returned'],
            ][$order->status] ?? [];

        if ($prefix === 'merchant') {
            $allowed = array_values(array_intersect($allowed, ['cancelled']));
        }

        if ($prefix === 'captain') {
            $allowed = array_values(array_diff($allowed, ['cancelled', 'returned']));
        }

        if ($prefix === 'admin' && !auth('staff')->user()->can('operate-delivery')) {
            $allowed = [];
        }

        $selectedStatus = in_array(old('status'), $allowed, true) ? old('status') : $allowed[0] ?? null;
    @endphp

    <div class="page-heading">
        <div>
            <span class="eyebrow">{{ $order->store->name }}</span>

            <h1>
                طلب <bdi>{{ substr($order->public_id, -10) }}</bdi>
            </h1>

            <p>
                {{ $order->merchant_reference }}
                ·
                {{ $order->created_at->timezone('Asia/Hebron')->format('Y/m/d H:i') }}
            </p>
        </div>

        <span class="badge">{{ $order->label }}</span>
    </div>

    <div class="dashboard-grid">
        <section class="panel">
            <h2>بيانات التوصيل</h2>

            <dl class="details-list">
                <div>
                    <dt>المستلم</dt>
                    <dd>
                        {{ $order->recipient_name }}
                        <br>
                        <a dir="ltr" href="tel:{{ $order->recipient_phone }}">
                            {{ $order->recipient_phone }}
                        </a>
                    </dd>
                </div>

                <div>
                    <dt>التوصيل</dt>
                    <dd>
                        {{ $order->destination_snapshot }}
                        ·
                        {{ $order->recipient_address }}
                    </dd>
                </div>

                <div>
                    <dt>الاستلام</dt>
                    <dd>
                        {{ $order->pickup_snapshot }}
                        <br>
                        <a dir="ltr" href="tel:{{ $order->pickup_phone }}">
                            {{ $order->pickup_phone }}
                        </a>
                    </dd>
                </div>

                <div>
                    <dt>موعد التوصيل</dt>
                    <dd>{{ $order->delivery_time_label }}</dd>
                </div>

                <div>
                    <dt>وصف الطرد</dt>
                    <dd>{{ $order->description }}</dd>
                </div>

                <div>
                    <dt>تعليمات</dt>
                    <dd>{{ $order->notes ?: '—' }}</dd>
                </div>

                <div>
                    <dt>الكابتن</dt>
                    <dd>{{ $order->captain?->name ?: 'بانتظار الإسناد' }}</dd>
                </div>
            </dl>

            <a class="button button-secondary" href="{{ route('tracking', $order->tracking_token) }}">
                رابط تتبع آمن للمستلم
            </a>
        </section>

        <section class="panel">
            <h2>التحصيل والتسعير</h2>

            @if ($order->status === 'failed')
                <p>
                    تعذّر التسليم. بانتظار إعادة المحاولة
                    أو تأكيد إعادة الطرد للمتجر.
                </p>

                <dl class="details-list">
                    <div>
                        <dt>أجرة التوصيل</dt>
                        <dd>{{ $money::show($order->fee_minor) }} ₪</dd>
                    </div>

                    <div>
                        <dt>أجرة الإعادة</dt>
                        <dd>{{ $money::show($order->return_fee_minor) }} ₪</dd>
                    </div>

                    <div>
                        <dt>الإجمالي المتوقع عند تأكيد الإرجاع</dt>
                        <dd>
                            {{ $money::show($order->fee_minor + $order->return_fee_minor) }}
                            ₪
                        </dd>
                    </div>
                </dl>

                <p class="hint">
                    لا يشمل قيمة البضاعة، ولا يُسجّل نهائيًا
                    على المتجر إلا بعد تأكيد إعادة الطرد.
                </p>
            @elseif ($order->status === 'returned')
                <p>تمت إعادة الطرد للمتجر؛ لا تُحصّل قيمة البضاعة.</p>

                <dl class="details-list">
                    <div>
                        <dt>المستحق المسجل على المتجر</dt>
                        <dd>
                            @if ($order->entry)
                                {{ $money::show($order->entry->fee_minor) }} ₪
                            @else
                                بانتظار القيد المالي
                            @endif
                        </dd>
                    </div>

                    @if ($prefix === 'captain')
                        <div>
                            <dt>أجرتك عند إعادة الطرد</dt>
                            <dd>
                                {{ $money::show($order->entry ? $order->entry->captain_pay_minor : $order->captain_return_pay_minor) }}
                                ₪
                            </dd>
                        </div>
                    @endif

                    @if ($prefix !== 'captain' && $order->entry)
                        <div>
                            <dt>صافي حساب المتجر المسجل</dt>
                            <dd>
                                {{ $money::show($order->entry->merchant_net_minor) }}
                                ₪
                            </dd>
                        </div>
                    @endif
                </dl>

                <p class="hint">
                    الرسوم تُسجّل على حساب المتجر.
                    ظهورها هنا لا يعني أن الكابتن استلمها نقدًا.
                </p>
            @elseif ($order->status === 'cancelled')
                <p>أُلغي الطلب قبل استلام الطرد؛ لا توجد رسوم للتحصيل.</p>

                <dl class="details-list">
                    <div>
                        <dt>المطلوب تحصيله</dt>
                        <dd>{{ $money::show(0) }} ₪</dd>
                    </div>
                </dl>
            @else
                @if ($prefix === 'captain')
                    <p>
                        {{ $order->fee_payer === 'merchant' ? 'المتجر (شامل التوصيل)' : 'المستلم (غير شامل التوصيل)' }}
                    </p>
                @endif

                @php
                    $priceFields =
                        $prefix === 'captain'
                            ? [
                                'collect_minor' => 'المطلوب تحصيله',
                                'captain_pay_minor' => 'أجرتك عند التسليم',
                                'captain_return_pay_minor' => 'أجرتك عند إعادة الطرد',
                            ]
                            : [
                                'goods_minor' => 'قيمة البضاعة',
                                'base_fee_minor' => 'الأجرة الأساسية',
                                'discount_minor' => 'الخصم',
                                'fee_minor' => 'أجرة التسليم',
                                'collect_minor' => 'المطلوب تحصيله',
                                'return_fee_minor' => 'أجرة إعادة الطرد',
                            ];
                @endphp

                <dl class="details-list">
                    @foreach ($priceFields as $key => $label)
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $money::show($order->$key) }} ₪</dd>
                        </div>
                    @endforeach

                    @if ($prefix !== 'captain')
                        @if ($prefix === 'admin')
                            <div>
                                <dt>أجرة الكابتن / حصة المكتب</dt>
                                <dd>
                                    {{ $money::show($order->captain_pay_minor) }}
                                    /
                                    {{ $money::show($order->fee_minor - $order->captain_pay_minor) }}
                                    ₪

                                    <small class="cell-note">
                                        {{ $order->captain_pay_mode === 'percent'
                                            ? number_format($order->captain_share_bps / 100, 2) . '% من الأجرة النهائية بعد الخصم'
                                            : 'أجرة كابتن ثابتة' }}
                                    </small>
                                </dd>
                            </div>
                        @endif

                        <div>
                            <dt>من يتحمل الأجرة؟</dt>
                            <dd>
                                {{ $order->fee_payer === 'merchant' ? 'المتجر (شامل التوصيل)' : 'المستلم (غير شامل التوصيل)' }}
                            </dd>
                        </div>

                        <div>
                            <dt>الميزة</dt>
                            <dd>{{ $order->discount_source ?: 'لا يوجد' }}</dd>
                        </div>

                        @if ($order->entry)
                            <div>
                                <dt>صافي حساب المتجر النهائي</dt>
                                <dd>
                                    {{ $money::show($order->entry->merchant_net_minor) }}
                                    ₪
                                </dd>
                            </div>
                        @endif
                    @endif
                </dl>
            @endif
        </section>
    </div>

    @if ($prefix === 'admin')
        @can('operate-delivery')
            @if (in_array($order->status, ['created', 'assigned']))
                <section class="panel section-space">
                    <h2>إسناد كابتن</h2>

                    <form method="post" action="{{ route('admin.orders.assign', $order) }}" class="filter-form">
                        @csrf

                        <div class="field">
                            <label for="captain">الكابتن</label>

                            <select id="captain" name="captain_id" required>
                                <option value="">اختر الكابتن</option>

                                @foreach ($captains as $c)
                                    @if ($c->captainProfile)
                                        <option value="{{ $c->id }}" @selected($order->captain_id === $c->id)>
                                            {{ $c->name }} · {{ $c->phone }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <button class="button button-primary" type="submit">
                            حفظ الإسناد
                        </button>
                    </form>
                </section>
            @endif
        @endcan
    @endif

    @if ($allowed)
        <section class="panel section-space">
            <h2>تحديث الحالة</h2>

            <form method="post" action="{{ route($prefix . '.orders.status', $order) }}" class="form-stack">
                @csrf
                @method('PATCH')

                <div class="form-grid">
                    <div class="field">
                        <label for="next-status">الإجراء</label>

                        <select data-transition-select id="next-status" name="status" required>
                            @foreach ($allowed as $s)
                                <option value="{{ $s }}" @selected($selectedStatus === $s)>
                                    {{ config('delivery.order_statuses.' . $s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (in_array('delivered', $allowed))
                        <x-field name="collected_amount" label="المبلغ المستلم فعليًا عند التسليم ₪" type="number"
                            min="0" step="0.01" hint="يجب أن يطابق كامل المبلغ المطلوب تحصيله." />
                    @endif
                </div>

                @if (in_array('delivered', $allowed))
                    <div data-delivery-payment>
                        @include('delivery.payment-fields')
                    </div>
                @endif

                <div data-transition-note>
                    <x-field name="note" label="سبب التعذر / الإلغاء / تأكيد إعادة الطرد للمتجر" minlength="3"
                        maxlength="500" />
                </div>

                <button class="button button-primary" type="submit">
                    تأكيد الإجراء
                </button>
            </form>
        </section>
    @endif

    @if ($order->payment)
        <section class="panel section-space">
            <h2>دفعة {{ $order->payment->method_label }}</h2>

            <dl class="details-list">
                <div>
                    <dt>الحساب المستلم</dt>
                    <dd>
                        {{ $order->payment->recipient === 'merchant' ? 'حساب صاحب المتجر' : 'حساب الكابتن' }}
                    </dd>
                </div>

                <div>
                    <dt>مرجع التحويل</dt>
                    <dd><bdi>{{ $order->payment->reference }}</bdi></dd>
                </div>

                <div>
                    <dt>المبلغ / التحقق</dt>
                    <dd>
                        {{ $money::show($order->payment->amount_minor) }} ₪
                        ·
                        {{ $order->payment->status_label }}
                    </dd>
                </div>

                @if ($prefix !== 'merchant')
                    <div>
                        <dt>رقم الحساب أو المحفظة</dt>
                        <dd>
                            <bdi>{{ $order->payment->account_label }}</bdi>
                        </dd>
                    </div>
                @endif

                @if ($order->payment->review_note)
                    <div>
                        <dt>ملاحظة المالية</dt>
                        <dd>{{ $order->payment->review_note }}</dd>
                    </div>
                @endif
            </dl>

            @if ($prefix === 'captain' && $order->payment->status === 'rejected')
                <form method="post" action="{{ route('captain.payments.correct', $order->payment) }}" class="form-stack">
                    @csrf
                    @method('PATCH')

                    <h3>تصحيح التحويل وإعادة إرساله</h3>

                    @include('delivery.payment-fields', ['correction' => true])

                    <button class="button button-primary" type="submit">
                        إعادة إرسال للتحقق
                    </button>
                </form>
            @endif
        </section>
    @endif

    <div class="dashboard-grid section-space">
        <section class="panel">
            <h2>سجل المتابعة</h2>

            <ol class="timeline">
                @foreach ($order->events as $e)
                    <li>
                        <strong>
                            {{ config('delivery.order_statuses.' . $e->status) }}
                        </strong>

                        <small class="cell-note">
                            {{ $e->created_at->timezone('Asia/Hebron')->format('Y/m/d H:i') }}
                        </small>

                        <p>{{ $e->note }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="panel">
            <h2>ملاحظات ودعم</h2>

            @forelse ($order->issues as $i)
                <div class="review-note">
                    <p>{{ $i->message }}</p>

                    <small>
                        {{ $i->created_at->timezone('Asia/Hebron')->format('m/d H:i') }}
                    </small>

                    @if ($i->reply)
                        <p>
                            <strong>رد الفريق:</strong>
                            {{ $i->reply }}
                        </p>
                    @endif
                </div>
            @empty
                <p class="muted">لا توجد ملاحظات حتى الآن.</p>
            @endforelse

            @if ($prefix === 'merchant')
                <form class="form-stack" method="post" action="{{ route('merchant.orders.issue', $order) }}">
                    @csrf

                    <div class="field">
                        <label for="message">أرسل ملاحظة لفريق التشغيل</label>

                        <textarea id="message" name="message" required minlength="5" maxlength="1000" rows="3">{{ old('message') }}</textarea>
                    </div>

                    <button class="button button-secondary" type="submit">
                        إرسال الملاحظة
                    </button>
                </form>
            @endif
        </section>
    </div>
@endsection
