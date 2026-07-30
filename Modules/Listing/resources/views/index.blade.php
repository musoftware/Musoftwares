@extends('listing::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('listing.name') !!}</p>
@endsection
