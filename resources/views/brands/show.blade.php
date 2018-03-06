@extends('layouts.app')

@section('title', $brand->name)

@section('content')
    <h1>{{ $brand->name }}</h1>
    <h2>{{ $brand->slogan }}</h2>
    <a href="{{ route('products.index', $brand) }}">View products</a>
    @auth
        <a href="{{ route('products.create', $brand) }}">Add a product</a>
    @endauth
@endsection