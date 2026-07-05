@extends('layouts.public', ['title' => '419 - '.__('ui.errors.419.title')])

@section('content')
    @include('errors.partials.public-error', [
        'code' => '419',
        'title' => __('ui.errors.419.title'),
        'description' => __('ui.errors.419.description'),
        'icon' => 'reload',
    ])
@endsection
