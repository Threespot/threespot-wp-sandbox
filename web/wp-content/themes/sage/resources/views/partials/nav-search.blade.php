{{-- Search field for main nav --}}
<form class="Nav-search {{ isset($classes) ? $classes : '' }}" method="get" action="{{ home_url('/') }}" role="search">
  <label class="u-screenreader" for="{{ $id }}">Keyword search</label>
  <input class="Nav-search-input" id="{{ $id }}" type="search" name="s" placeholder="Search" value="{{ !empty($search_query) ? $search_query : '' }}" results="0" spellcheck>
  <button class="Nav-search-submit" type="submit">
    {!! App\svg(['file' => 'icons/search', 'class'=>'icon', 'width'=>18]) !!}
    <span class="u-screenreader">Submit search</span>
  </button>
</form>
