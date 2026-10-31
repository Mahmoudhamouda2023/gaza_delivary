@extends('layouts.shell')
@section('title','سجل العمليات')
@section('content')
<div class="page-heading"><div><h1>سجل العمليات</h1><p>تغييرات الإعدادات والعروض والعمليات المالية. سجل حالات كل طلب داخل صفحته.</p></div></div><section class="panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>الوقت</th><th>المنفذ</th><th>العملية</th><th>السجل</th><th>التفاصيل</th></tr></thead><tbody>@foreach($rows as $a)<tr><td>{{ $a->created_at->timezone('Asia/Hebron')->format('Y/m/d H:i') }}</td><td>{{ $a->staff_id?'موظف #'.$a->staff_id:'تاجر #'.$a->user_id }}</td><td dir="ltr">{{ $a->event }}</td><td>{{ $a->subject }}</td><td><details><summary>عرض</summary><pre dir="ltr">{{ json_encode($a->data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></details></td></tr>@endforeach</tbody></table></div>{{ $rows->links() }}</section>
@endsection
