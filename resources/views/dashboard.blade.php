@extends('layouts.app')

@section('title', 'Your Dashboard')

@section('content')
    <h1>Your dashboard</h1>
    <a href="{{ route('brands.create') }}">Create a brand</a>
@endsection