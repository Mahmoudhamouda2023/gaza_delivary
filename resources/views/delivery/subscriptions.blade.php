@extends('layouts.shell')
@section('title','هدايا التوصيل')
@section('content')
<div class="page-heading"><div><span class="eyebrow">علاقات المتاجر</span><h1>هدايا التوصيل</h1><p>إدارة هدايا التوصيل للمتاجر.</p></div></div>
<section class="panel section-space"><h2>منح هدية توصيل</h2><form class="form-stack" method="post" action="{{ route('admin.gifts.store') }}">@csrf<div class="form-grid"><div class="field"><label for="gift-store">المتجر</label><select id="gift-store" name="store_id" required><option value="">اختر المتجر</option>@foreach($stores as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div><x-field name="quantity" label="عدد التوصيلات المجانية" type="number" min="1" max="10000" required/><x-field name="expires_on" label="صالحة حتى" type="date" required/><x-field name="reason" label="سبب الهدية / مناسبتها" minlength="3" maxlength="500" required/></div><button class="button button-primary">منح الهدية</button></form></section>
<section class="panel section-space"><h2>أحدث الهدايا</h2>@foreach($credits as $c)<div class="review-note">{{ $c->store->name }} · المتبقي {{ $c->remaining }} من {{ $c->quantity }} · حتى {{ $c->expires_on->format('Y/m/d') }}<p>{{ $c->reason }}</p></div>@endforeach</section>
@endsection
