@props(['name'])
<svg {{ $attributes->merge(['class' => 'icon']) }} aria-hidden="true" focusable="false"><use href="{{ asset('assets/icons.svg') }}#{{ $name }}"></use></svg>
