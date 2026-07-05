@extends('layouts.public', ['title' => '503 - '.__('ui.errors.503.title')])

@section('content')
    @include('errors.partials.public-error', [
        'code' => '503',
        'title' => __('ui.errors.503.title'),
        'description' => __('ui.errors.503.description'),
        'icon' => 'sparkles',
    ])
@endsection
