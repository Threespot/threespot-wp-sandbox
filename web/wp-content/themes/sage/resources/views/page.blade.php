{{-- Page template --}}
@extends('layouts.app')

@section('content')
  @include('partials.page-header', [
    'post_parent' => $post->post_parent,
    'excerpt' => $post->post_excerpt,// untruncated excerpt
    'image_id' => get_post_thumbnail_id($post->ID) ?? false,
  ])

  <div class="l-padding">
    <div class="u-richtext f-scale l-wrap--narrow l-block-wrap">
      @while(have_posts()) @php the_post() @endphp
        @php the_content() @endphp
      @endwhile
    </div>
  </div>
@endsection
