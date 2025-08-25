{{-- Post template --}}
@extends('layouts.app')

@section('content')
  @include('partials.post-header')

  <div class="l-padding">
    <div class="u-richtext f-scale l-wrap--narrow l-block-wrap">
      @while(have_posts()) @php the_post() @endphp
        @php the_content() @endphp
      @endwhile
    </div>
  </div>
@endsection
