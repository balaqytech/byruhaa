@props(['name'])

@php($component = 'icons.'.$name)

@if (View::exists('components.'.$component))
    <x-dynamic-component :component="$component" {{ $attributes }} />
@else
    <x-icons.icon :name="$name" {{ $attributes }} />
@endif
