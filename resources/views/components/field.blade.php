@props(['name', 'label', 'type' => 'text', 'hint' => null])
@php $fieldId = $attributes->get('id', 'field-'.$name.'-'.\Illuminate\Support\Str::random(6)); @endphp
<div class="field">
    <label for="{{ $fieldId }}">{{ $label }}</label>
    <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}"
        @if(!in_array($type, ['password', 'file'])) value="{{ old($name, $attributes->get('value')) }}" @endif
        @error($name) aria-invalid="true" @enderror
        aria-describedby="{{ $hint ? 'hint-'.$fieldId : '' }} {{ $errors->has($name) ? 'error-'.$fieldId : '' }}"
        {{ $attributes->except('value', 'id') }}>
    @if($hint)<small class="hint" id="hint-{{ $fieldId }}">{{ $hint }}</small>@endif
    @error($name)<small class="field-error" id="error-{{ $fieldId }}">{{ $message }}</small>@enderror
</div>
