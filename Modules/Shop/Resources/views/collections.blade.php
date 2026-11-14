@extends('shop::layout')

@section('content')
    <h1 class="title">الأقسام</h1>

    @if ($categories->isNotEmpty())
        @include('shop::partials.categories', ['categories' => $categories])
    @else
        <p class="lead">لسه مفيش أقسام. ارجع لنا قريب.</p>
    @endif
@endsection
