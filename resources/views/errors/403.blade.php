@extends('layouts.public', ['title' => '403 - '.__('ui.errors.403.title')])

@section('content')
    @include('errors.partials.public-error', [
        'code' => '403',
        'title' => __('ui.errors.403.title'),
        'description' => __('ui.errors.403.description'),
        'icon' => 'lock-key',
    ])
@endsection
