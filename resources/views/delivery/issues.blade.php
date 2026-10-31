@extends('layouts.shell')
@section('title','متابعة الملاحظات')
@section('content')
<div class="page-heading"><div><span class="eyebrow">خدمة المتاجر</span><h1>ملاحظات تحتاج متابعة</h1></div></div><section class="panel">@forelse($issues as $i)<div class="review-note"><a class="text-link" href="{{ route('admin.orders.show',$i->order) }}">{{ $i->order->store->name }} · {{ substr($i->order->public_id,-10) }}</a><p>{{ $i->message }}</p><form method="post" action="{{ route('admin.issues.resolve',$i->id) }}" class="form-stack">@csrf @method('PATCH')<x-field name="reply" label="الرد الذي سيظهر للتاجر" minlength="3" maxlength="1000" required/><button class="button button-primary">حفظ الرد وإغلاق الملاحظة</button></form></div>@empty<div class="empty-state">لا توجد ملاحظات مفتوحة.</div>@endforelse{{ $issues->links() }}</section>
@endsection
