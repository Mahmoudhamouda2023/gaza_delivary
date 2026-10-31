@extends('layouts.shell')
@section('title', 'الطلبات')
@section('breadcrumb', 'الطلبات')
@section('content')
    <div class="page-heading">
        <div><span class="eyebrow">تشغيل التوصيل</span>
            <h1>الطلبات</h1>
            <p>كل طلب، وحالته، وتفاصيل متابعته.</p>
        </div>
        <div class="actions">
            @if ($prefix === 'merchant')
                <a class="button button-primary" href="{{ route('merchant.orders.create') }}">طلب توصيل جديد +</a>
                @endif @if ($prefix !== 'captain')
                    <a class="button button-secondary"
                        href="{{ route($prefix . '.orders.export', request()->query()) }}">تصدير
                        CSV</a>
                @endif
        </div>
    </div>
    <section class="panel">
        <form class="filter-form" method="get"><x-field name="q" label="ابحث برقم الطلب أو المرجع أو المستلم"
                :value="request('q')" maxlength="100" />
            <div class="field"><label for="status">الحالة</label><select id="status" name="status">
                    <option value="">جميع الحالات</option>
                    @foreach (config('delivery.order_statuses') as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div><x-field name="from" label="من تاريخ" type="date" :value="request('from')" /><x-field name="to"
                label="إلى تاريخ" type="date" :value="request('to')" /><button class="button button-primary">عرض
                النتائج</button>
        </form>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الطلب / المرجع</th>
                        <th>المتجر والمستلم</th>
                        <th>الحالة</th>
                        <th>التحصيل / رسوم الإرجاع</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                        <tr>
                            <td><a class="text-link"
                                    href="{{ route($prefix . '.orders.show', $o) }}"><bdi>{{ substr($o->public_id, -10) }}</bdi></a><small
                                    class="cell-note">{{ $o->merchant_reference }}</small></td>
                            <td>{{ $o->store->name }}<small class="cell-note">{{ $o->recipient_name }} ·
                                    {{ $o->destination_snapshot }}</small></td>
                            <td><span class="badge">{{ $o->label }}</span><small
                                    class="cell-note">{{ $o->captain?->name }}</small></td>
                            <td>
                                @if ($o->status === 'failed')
                                    <span class="muted">بانتظار إعادة المحاولة أو الإرجاع</span>
                                @elseif($o->status === 'returned')
                                    @if ($o->entry)
                                        {{ \App\Services\Delivery\Money::show($o->entry->fee_minor) }}
                                        <small class="cell-note">
                                            مستحق على المتجر — دون قيمة البضاعة
                                        </small>
                                    @else
                                        <span class="muted">بانتظار القيد المالي</span>
                                    @endif
                                @elseif($o->status === 'cancelled')
                                    {{ \App\Services\Delivery\Money::show(0) }}
                                    <small class="cell-note">إلغاء قبل الاستلام</small>
                                @else
                                    {{ \App\Services\Delivery\Money::show($o->collect_minor) }}
                                @endif
                            </td>
                            <td>{{ $o->created_at->timezone('Asia/Hebron')->format('m/d H:i') }}<small
                                    class="cell-note">توصيل: {{ $o->delivery_time_label }}</small></td>
                    </tr>@empty<tr>
                            <td colspan="5">
                                <div class="empty-state">لا توجد طلبات مطابقة.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $orders->links() }}
    </section>
@endsection
ئ
