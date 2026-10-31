@extends('layouts.shell')
@section('title', 'أمان الحساب')
@section('breadcrumb', 'أمان الحساب')
@section('content')
<div class="page-heading"><div><span class="eyebrow">أمان الحساب</span><h1>{{ $account->must_change_password ? 'خلّي كلمة المرور إلك وحدك' : 'تغيير كلمة المرور' }}</h1><p>{{ $account->must_change_password ? 'قبل ما تبدأ، بدّل كلمة المرور المؤقتة بكلمة خاصة فيك.' : 'تغييرها ينهي صلاحية جلساتك الأخرى ويحافظ على جلستك الحالية.' }}</p></div><span class="square-icon"><x-icon name="lock"/></span></div>
<section class="panel narrow-panel"><form class="form-stack" method="post" action="{{ route($guard.'.password.update') }}">@csrf @method('PUT')
    <x-field name="current_password" label="كلمة المرور الحالية" type="password" autocomplete="current-password" required maxlength="256"/>
    <x-field name="password" label="كلمة المرور الجديدة" type="password" autocomplete="new-password" required minlength="8" maxlength="256" hint="8 خانات على الأقل، تتضمن أحرفًا وأرقامًا، وتختلف عن الحالية."/>
    <x-field name="password_confirmation" label="تأكيد كلمة المرور الجديدة" type="password" autocomplete="new-password" required minlength="8" maxlength="256"/>
    <button class="button button-primary" type="submit">حفظ كلمة المرور <x-icon name="check"/></button>
</form></section>
@endsection
