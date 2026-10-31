@if(session('status'))<div class="notice notice-success" role="status"><x-icon name="check"/><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="notice notice-error" role="alert"><x-icon name="alert"/><div><strong>راجع البيانات التالية</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>@endif
