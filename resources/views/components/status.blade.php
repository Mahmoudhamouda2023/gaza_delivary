@props(['value'])
@php($status = $value instanceof \BackedEnum ? $value->value : $value)
<span class="badge badge-{{ $status }}"><span class="status-dot"></span>{{ config('delivery.status_labels.'.$status, $status) }}</span>
