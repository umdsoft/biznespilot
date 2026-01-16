@extends('landing.layouts.landing')

@section('content')
    {{-- Header / Navigation --}}
    @include('landing.partials.header')

    {{-- Hero Section --}}
    @include('landing.partials.hero')

    {{-- Problem Section --}}
    @include('landing.partials.problem')

    {{-- Features Section --}}
    @include('landing.partials.features')

    {{-- AI Features Carousel Section --}}
    @include('landing.partials.features-carousel')

    {{-- How It Works Section --}}
    @include('landing.partials.how-it-works')

    {{-- Benefits Section --}}
    @include('landing.partials.benefits')

    {{-- Testimonials Section --}}
    @include('landing.partials.testimonials')

    {{-- FAQ Section --}}
    @include('landing.partials.faq')

    {{-- CTA Section --}}
    @include('landing.partials.cta')

    {{-- Footer --}}
    @include('landing.partials.footer')
@endsection
