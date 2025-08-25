{{-- Archive --}}
@extends('layouts.app')

@section('content')
  @if ($image_id || $term->description)
    @include('partials.page-header', [
      'excerpt' => $term->description ?: false,
      'image_id' => $image_id,
      'image_alt' => App\get_img_alt($image_id),
    ])
  @else
    @include('partials/search-header')
  @endif

  <div class="l-padding">
    <div class="l-wrap--narrow l-block-wrap">
      {{-- Filters --}}
      @include('partials/filters')
      @include('partials/filter-tags')

      @if (!have_posts())
        <div class="mb-7 mt-5 u-richtext f-scale">
          {{ __('Sorry, no results were found. Try removing filters.', 'sage') }}
        </div>
      @else
        <div class="Listing">
          <p class="Listing-results">{!! $results_info !!}</p>

          <ul class="Listing-list" role="list">
            @while(have_posts()) @php the_post() @endphp
              @include('partials/listing-item', [
                'post_obj' => get_post(),
                'show_excerpts' => true,
              ])
            @endwhile
          </ul>
        </div>{{-- end Listing --}}

        @include('partials/pagination')
      @endif

    </div>
  </div>
@endsection
