{{-- Search header --}}
{{-- Note: This is also the fallback on archive pages so we need the option to hide the search field --}}
<div class="SearchHeader l-padding">
  <div class="SearchHeader-wrap l-wrap">
    <div class="SearchHeader-content">
      <h1 class="SearchHeader-title f-title" id="title">
        {!! $title !!}
      </h1>
      {{-- Search field --}}
      @if (is_search())
        <form class="SearchHeader-form" method="get" action="{{ home_url('/') }}" role="search">
          <label class="u-screenreader" for="search-banner">Keyword search</label>
          <input class="SearchHeader-form-input" id="search-banner" type="search" name="s" placeholder="Search" value="{{ !empty($search_query) ? $search_query : '' }}" results="0" spellcheck>

          {{-- FIXME: Include hidden fields for filters to avoid resetting them when the search term is changed --}}
          {{--
          @php
            $hidden_query_vars = ['post_type', 'topic', 'event_type'];
          @endphp
          @foreach ($hidden_query_vars as $query_var)
            @if ($value = get_query_var($query_var))
              <input type="hidden" name="{{ $query_var }}" value="{{ $value }}">
            @endif
          @endforeach
          --}}

          <button class="SearchHeader-form-submit" type="submit">
            {!! App\svg(['file' => 'icons/search', 'class'=>'icon', 'width'=>14]) !!}
            <span class="u-screenreader">Submit search</span>
          </button>
        </form>
      @endif
    </div>
  </div>
</div><!-- end SearchHeader -->
