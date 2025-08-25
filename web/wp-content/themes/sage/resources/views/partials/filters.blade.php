{{-- Content filters --}}
<div class="Filters" id="results">
  {{-- Mobile toggle --}}
  <button class="Filters-toggle Filters-heading no-js-hide" type="button" data-expands="filters" data-expands-height>
    <span class="is-active-hide" aria-label="Open filters">Filter by</span>
    <span class="is-active-show" aria-label="Close filters">Filter by</span>
    {!! App\svg([
      'file' => 'icons/chev-down',
      'class' => 'icon',
      'width' => 15,
      'sprite' => true
    ]) !!}
  </button>

  {{-- Desktop heading --}}
  <h2 class="Filters-heading">Filter by</h2>

  <form class="Filters-form" id="filters" method="get" action="{{ $filters_form_action }}" aria-label="Filters">
    <fieldset class="Filters-facets">
      <legend class="Filters-label u-screenreader">Filters</legend>

      {{-- NOTE: We have to add a hidden search field so the keyword isn’t lost after applying the filters --}}
      @if (is_search())
        <input type="hidden" name="s" value="{{ $search_query }}">
      @endif

      {{-- Post type filter (excluding posts and pages) --}}
      @php
        $post_type_query_var = get_query_var('post_type');
        $show_post_type_filter = is_search() || $current_taxonomy == 'topic';
      @endphp
      <div class="Filters-facets-item is-type {{ $show_post_type_filter ? '' : 'u-hide' }}">
        <label class="Filters-label" for="post-type-filter">Type</label>
        <select class="Filters-select" id="post-type-filter" name="post_type">
          <option value="">All</option>
          @foreach ($filter_post_type_objects as $post)
            {{-- Optionally hide the filter for specific post types --}}
            @if (!in_array($post->name, ['person']))
              <option value="{{ $post->name }}" {{ $post_type_query_var == $post->name ? 'selected' : ''}}>
                {{ $post->label }}
              </option>
            @endif
          @endforeach
        </select>
      </div>

      {{-- FIXME: Topic filter example --}}
      @include('partials.filter-item', [
        'taxonomy_name' => 'topic',
        'show_filter' => is_search() || in_array($current_post_type, ['event', 'news']),
      ])

      {{-- FIXME: Event Type filter example --}}
      @include('partials.filter-item', [
        'taxonomy_name' => 'event_type',
        'show_filter' => $current_post_type == 'event',
      ])

      {{-- Year filter --}}
      {{-- NOTE: Uncomment filter_years() in Search.php if using --}}
      {{--
      @php
        $post_type_query_var = get_query_var('year');
      @endphp
      <div class="Filters-facets-item is-year">
        <label class="Filters-label" for="year-filter">Year</label>
        <pre>$filter_years: <code>{{ var_dump($filter_years) }}</code></pre>
        <select class="Filters-select" id="year-filter" name="year">
          <option value="">All</option>
          @foreach ($filter_years as $year)
            <option value="{{ $year }}" {{ $post_type_query_var == $year ? 'selected' : ''}}>
              {{ $year }}
            </option>
          @endforeach
        </select>
      </div>
      --}}

    </fieldset>

    {{-- Sort by date example --}}
    {{-- @php
      $show_filter = in_array($current_post_type, ['event']);
      $query_var = get_query_var('order');
    @endphp
    @if($show_filter)
      <div class="Filters-facets-item">
        <label class="Filters-label" for="date-sort">Sort by Date</label>
        <select class="Filters-select" id="date-sort" name="order">
          <option value="DSC" {{ $query_var == "DSC" ? "selected" : "" }}>Newest</option>
          <option value="ASC" {{ $query_var == "ASC" ? "selected" : "" }}>Oldest</option>
        </select>
      </div>
    @endif --}}

    <div class="Filters-buttons">
      <button class="Filters-buttons-apply btn-solid" type="submit" aria-label="Apply filters">Apply</button>
      @if ($active_filters)
        <a class="Filters-buttons-reset" href="{{ $filter_reset_url }}">Reset filters</a>
      @endif
    </div>{{-- end Filters-buttons --}}
  </form>
</div><!-- end Filters -->
