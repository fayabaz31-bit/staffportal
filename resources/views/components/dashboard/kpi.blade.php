@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'route' => null,
])

@php
    $tag = $route !== null ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($route !== null) href="{{ $route }}" @endif
    {{ $attributes->class(['kpi-tile', 'kpi-tile-'.$tone]) }}
>
    <span class="kpi-label">{{ $label }}</span>
    <span class="kpi-value">{{ $value }}</span>
    @if ($hint !== null)
        <span class="kpi-hint">{{ $hint }}</span>
    @endif
</{{ $tag }}>
