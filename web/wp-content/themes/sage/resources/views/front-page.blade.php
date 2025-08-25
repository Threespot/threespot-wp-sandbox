{{-- Homepage --}}
@extends('layouts.app')

@section('content')
  <div class="l-padding">
    <div class="u-richtext f-scale l-wrap l-block-wrap">
      @while(have_posts()) @php the_post() @endphp
        @php the_content() @endphp
      @endwhile
    </div>
  </div>
@endsection
