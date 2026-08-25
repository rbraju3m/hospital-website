{{-- Slider-led: the panel's own slides across the top, then the same bands in
     the same order as the classic layout.

     The slider falls back to the classic hero when there is nothing to show.
     A layout with no content behind it must not produce an empty page — this
     is the one page on the site that always has to render, and "somebody
     switched the layout before writing the slides" is a Tuesday. --}}
@extends('layouts.site')

@section('title', __('home.meta_title'))
@section('meta_description', __('home.meta_description', [
    'name' => setting('site_name'),
    'beds' => setting('bed_count'),
    'doctors' => setting('stat_doctors'),
]))

@section('content')

@if ($slides->isNotEmpty())
    @include('pages.home.bands.slider')
@else
    @include('pages.home.bands.hero')
@endif

@include('pages.home.bands.quick-actions')
@include('pages.home.bands.departments')
@include('pages.home.bands.doctors')
@include('pages.home.bands.services')
@include('pages.home.bands.why')
@include('pages.home.bands.packages')
@include('pages.home.bands.testimonials')
@include('pages.home.bands.posts')
@include('pages.home.bands.gallery')
@include('pages.home.bands.visit')

@endsection
