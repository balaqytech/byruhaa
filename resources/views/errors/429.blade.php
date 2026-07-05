@extends('layouts.public', ['title' => '429 - '.__('ui.errors.429.title')])

@section('content')
    @include('errors.partials.public-error', [
        'code' => '429',
        'title' => __('ui.errors.429.title'),
        'description' => __('ui.errors.429.description'),
        'icon' => 'clock-01',
    ])
@endsection
