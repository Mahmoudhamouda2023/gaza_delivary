@extends('layouts.shell')
@section('title', 'حالة تسجيل المتجر')
@section('breadcrumb', 'حالة التسجيل')
@section('content')
<div class="page-heading"><div><span class="eyebrow">حالة التسجيل</span><h1>أهلًا، {{ $account->name }}</h1><p>تابع طلب تسجيل متجرك من هنا.</p></div><x-status :value="$account->store->status"/></div>
<section class="panel status-panel"><span class="large-icon"><x-icon :name="$account->store->status->value === 'rejected' ? 'alert' : 'clock'"/></span>
    @if($account->store->status->value === 'rejected')<h2>طلب متجرك لم يُعتمد بعد</h2><p>راجع ملاحظة الإدارة أدناه. يمكن لفريقنا إعادة الطلب للمراجعة بعد معالجة السبب.</p>@else<h2>وصلنا طلب تسجيلك</h2><p>فريقنا بيراجع بيانات {{ $account->store->name }}، وبعد اعتماد الحساب بنرتّب معك تفاصيل الخدمة.</p>@endif
    @if($account->store->review_reason)<div class="review-note"><strong>ملاحظة الإدارة</strong><p>{{ $account->store->review_reason }}</p></div>@endif
    <dl class="details-list"><div><dt>اسم المتجر</dt><dd>{{ $account->store->name }}</dd></div><div><dt>رقم التواصل</dt><dd dir="ltr">{{ $account->phone }}</dd></div><div><dt>تاريخ التسجيل</dt><dd>{{ $account->created_at->timezone('Asia/Hebron')->format('Y/m/d') }}</dd></div></dl>
    <a class="button button-secondary" href="{{ route('merchant.status') }}"><x-icon name="refresh"/>تحديث الحالة</a>
</section>
@endsection
