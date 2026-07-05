@extends('layouts.public', ['title' => '500 - '.__('ui.errors.500.title')])

@section('content')
    @include('errors.partials.public-error', [
        'code' => '500',
        'title' => __('ui.errors.500.title'),
        'description' => __('ui.errors.500.description'),
        'icon' => 'alert-02',
    ])
@endsection
