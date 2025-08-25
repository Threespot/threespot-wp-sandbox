{{-- Active filter tags --}}
@if (!empty($active_filters))
  <div class="FilterTags">
    <ul class="FilterTags-list" role="list">
      @foreach ($active_filters as $param => $var)
        {{-- Post type filters require different logic than taxonomy filters --}}
        @if (is_search() && $param == 'post_type')
          @php
            $post_type_obj = get_post_type_object($var);
          @endphp
          @if ($post_type_obj)
            <li class="FilterTags-item">
              <a class="FilterTags-link FilterTags-link--toggle"
                href="{{ remove_query_arg($param) }}"
                aria-label="remove {{ $post_type_obj->labels->name }} filter.">
                {!! $post_type_obj->labels->name !!}
                {!! App\svg(['file' => 'icons/close', 'class' => 'icon', 'width' => 11]) !!}
              </a>
            </li>
          @endif
        @else
          {{-- Taxonomy filters --}}
          @php
            $term = get_term_by('slug', $var, $param);
          @endphp
          @if ($term)
            <li class="FilterTags-item">
              <a class="FilterTags-link FilterTags-link--toggle"
                href="{{ remove_query_arg($param) }}"
                aria-label="remove {{ $term->name }} filter.">
                {!! $term->name !!}
                {!! App\svg(['file' => 'icons/close', 'class' => 'icon', 'width' => 11]) !!}
              </a>
            </li>
          @endif
        @endif
      @endforeach
      {{-- Clear all filters (only show when > 1 filter)--}}
      @if (count($active_filters) > 1)
        <li class="FilterTags-item">
          <a class="FilterTags-link FilterTags-link--reset"
            href="{{ $filter_reset_url }}"
            aria-label="remove all filters.">
            Clear All
          </a>
        </li>
      @endif
    </ul>
  </div><!-- end FilterTags -->
@endif
