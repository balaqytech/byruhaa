@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
])

@section('content')
    <livewire:store.checkout />
@endsection
