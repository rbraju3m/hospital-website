{{-- Compact: content first.

     Same hero, but the page gets to the things a visitor came for sooner —
     departments, doctors and services before the case for the hospital, the
     editorial and the gallery. For a site whose visitors already know who they
     are dealing with and are looking for a department and a phone number.

     Nothing is dropped: every band the classic layout renders is here, in a
     different order, and each still answers to its own Site controls switch. --}}
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
@include('pages.home.bands.packages')
@include('pages.home.bands.visit')
@include('pages.home.bands.why')
@include('pages.home.bands.testimonials')
@include('pages.home.bands.posts')
@include('pages.home.bands.gallery')

@endsection
