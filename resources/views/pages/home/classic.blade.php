{{-- The home page as it has always been: a photographic hero with the booking
     form beside it, then the bands in the order the site was designed in.

     The bands themselves live one directory down and are shared with every
     other layout — a template decides what comes first and what the top of the
     page looks like, never what a section says. --}}
@extends('layouts.site')

@section('title', __('home.meta_title'))
@section('meta_description', __('home.meta_description', [
    'name' => setting('site_name'),
    'beds' => setting('bed_count'),
    'doctors' => setting('stat_doctors'),
]))

@section('content')

@include('pages.home.bands.hero')
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
