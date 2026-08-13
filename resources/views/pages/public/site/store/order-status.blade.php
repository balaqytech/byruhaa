@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
])

@section('content')
    <main class="min-h-[70vh] bg-[#f6fbf8] px-4 py-16 dark:bg-[#07120f] sm:px-6 lg:px-8 lg:py-24">
        <livewire:store.order-status :payment-token="$order->payment_token" />
    </main>
@endsection
