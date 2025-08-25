{{-- Search --}}
@extends('layouts.app')

@section('content')
  @include('partials/search-header')

  <div class="l-padding">
    <div class="l-wrap--narrow l-block-wrap">
      {{-- @include('partials/filters') --}}
      {{-- @include('partials/filter-tags') --}}

      @if (!have_posts())
        <div class="mb-7 mt-5 u-richtext f-scale">
          {{ __('Sorry, no results were found. Try changing your search term or removing filters.', 'sage') }}
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
