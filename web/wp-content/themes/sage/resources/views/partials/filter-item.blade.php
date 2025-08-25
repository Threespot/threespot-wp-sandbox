{{-- Filter item --}}
@php
  $query_var = get_query_var($taxonomy_name);
  $label = isset($custom_label) ? $custom_label : get_taxonomy($taxonomy_name)->labels->singular_name;
@endphp
<div class="Filters-facets-item {{ $show_filter ? '' : 'u-hide' }}">
  <label class="Filters-label" for="{{ $taxonomy_name }}">{{ $label }}</label>
  @php
    $terms = get_terms([
      'taxonomy' => $taxonomy_name,
      'parent' => 0,// excludes children
      'hide_empty' => true,
    ]);
  @endphp
  <select class="Filters-select" id="{{ $taxonomy_name }}" name="{{ $taxonomy_name }}">
    <option value="">All</option>
    @foreach ($terms as $term)
      <option value="{{ $term->slug }}" {{ $query_var == $term->slug ? "selected" : "" }}>
        {!! $term->name !!}
      </option>
    @endforeach
  </select>
</div>
