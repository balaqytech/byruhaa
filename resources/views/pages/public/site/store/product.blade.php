@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'metaImage' => $metaImage,
])

@section('content')
    <livewire:store.product-detail :product="$product" />
@endsection
