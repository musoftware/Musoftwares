@extends('shared::layouts.master')

@section('content')
    <h1>{{ __('general.hello_world') }}</h1>

    <p>Module: {!! config('shared.name') !!}</p>
@endsection
