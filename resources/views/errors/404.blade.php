@extends('layouts.public', ['title' => '404 - '.__('ui.errors.404.title')])

@section('content')
    @include('errors.partials.public-error', [
        'code' => '404',
        'title' => __('ui.errors.404.title'),
        'description' => __('ui.errors.404.description'),
        'icon' => 'map-pin',
    ])
@endsection
