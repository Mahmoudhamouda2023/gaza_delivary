@extends('layouts.shell')
@section('title', 'إضافة حساب للفريق')
@section('breadcrumb', 'إضافة حساب')
@section('content')
<a class="back-link" href="{{ route('admin.staff.index') }}"><x-icon name="arrow"/>فريق العمل</a><div class="page-heading"><div><span class="eyebrow">حساب جديد</span><h1>أضف شخصًا للفريق</h1><p>حدّد دوره وكلمة مرور مؤقتة، وسيغيّرها عند أول دخول.</p></div></div>
<section class="panel narrow-panel"><form method="post" action="{{ route('admin.staff.store') }}" class="form-stack">@csrf
<div class="form-grid"><x-field name="name" label="الاسم" required maxlength="120" autocomplete="name"/><x-field name="phone" label="رقم الجوال" type="tel" dir="ltr" required autocomplete="tel" maxlength="10" minlength="10" inputmode="numeric" pattern="[0٠۰][5٥۵][0-9٠-٩۰-۹]{8}"/></div><x-field name="email" label="البريد الإلكتروني (اختياري)" type="email" dir="ltr" autocomplete="email" maxlength="254"/>
<fieldset class="role-picker"><legend>الدور الوظيفي</legend>@foreach(config('delivery.role_labels') as $role => $label)<label><input type="checkbox" name="roles[]" value="{{ $role }}" @checked(in_array($role, old('roles', [])))><span>{{ $label }}</span></label>@endforeach<small class="hint">اختر أقل صلاحيات يحتاجها الشخص لإنجاز عمله.</small></fieldset>
<div class="field"><label for="vehicle-type">وسيلة التوصيل (للكابتن)</label><select id="vehicle-type" name="vehicle_type"><option value="">لا ينطبق</option><option value="bicycle" @selected(old('vehicle_type') === 'bicycle')>دراجة هوائية</option><option value="electric_bicycle" @selected(old('vehicle_type') === 'electric_bicycle')>دراجة كهربائية</option></select></div>
<x-field name="password" label="كلمة مرور مؤقتة" type="password" autocomplete="new-password" required minlength="8" maxlength="256" hint="8 خانات على الأقل، تتضمن أحرفًا وأرقامًا. سلّمها لصاحب الحساب بشكل خاص."/><x-field name="password_confirmation" label="تأكيد كلمة المرور المؤقتة" type="password" autocomplete="new-password" required minlength="8" maxlength="256"/><button class="button button-primary" type="submit">إنشاء الحساب <x-icon name="plus"/></button>
</form></section>
@endsection
