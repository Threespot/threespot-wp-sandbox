@extends('layouts.app')

@section('content')
  <div class="PostHeader l-padding">
    <div class="PostHeader-wrap l-wrap">
      <div class="PostHeader-content">
        <h1 class="PostHeader-title" id="title">
          {!! $title !!}
        </h1>
      </div>
    </div><!-- end PostHeader-wrap -->
  </div><!-- end PostHeader -->

  <div class="l-padding">
    <div class="l-wrap">
      <div class="u-richtext f-scale l-block-wrap">
        <p>Sorry, the page you requested has moved or is no longer available.</p>
        <a href="{{ home_url('/') }}">Go back home</a>
      </div>
    </div>
  </div>
@endsection
