@extends('layouts.app')

@section('title', $product->name)

@section('content')
    @auth
        <a class="text-blue-500" href="{{ route('product-models.edit', $product->product_model_id) }}">Edit</a>
        <p>Sold: {{ $product->itemsSold() }}</p>
        <p>Revenue: {{ $product->revenue() }}</p>
        <p>Items Remaining: {{ $product->item_quantity }}</p>
    @endauth

    <div class="py-4 container mx-auto">
        <product-show
            :model="{{ $model }}"
            :attributes="{{ $model->attributes() }}"
            :urls="{{ $urls }}"
            product-id="{{ $product->id }}"
            user-email="{{ optional(auth()->user())->email }}">
        </product-show>
    </div>
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