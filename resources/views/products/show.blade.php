@extends('layouts.app')

@section('title', $product->name)

@section('content')
    @auth
        <a href="{{ route('products.edit', $product) }}">Edit</a>
        <h2>Sold: {{ $product->itemsSold() }}</h2>
        <h2>Revenue: {{ $product->revenue() }}</h2>
    @endauth
    <img src="{{ url($product->image_path) }}" alt="product" height="100px">
    <h1>{{ $product->name }}</h1>
    <h2>Items Remaining: {{ $product->itemsRemaining() }}</h2>
    <h1>{{ $product->description }}</h1>
    <h1>{{ $product->price() }}</h1>
    <product-checkout product-id="{{ $product->id }}" product-price="{{ $product->price }}"></product-checkout>
@endsection

@push('scripts')
    <meta name="turbolinks-visit-control" content="reload">
    <script src="https://checkout.stripe.com/checkout.js"></script>
    <script>
        var App = {
            stripeKey: '{{ config('services.stripe.key') }}',
        }
    </script>
@endpush