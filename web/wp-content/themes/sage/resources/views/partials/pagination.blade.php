{{-- Pagination --}}
{{-- https://developer.wordpress.org/reference/functions/paginate_links/ --}}
{{-- https://designsystem.digital.gov/components/pagination/ --}}
@if ($pagination_desktop)
  <nav class="Pagination l-wrap--medium" aria-label="Pagination">
    {{-- Desktop --}}
    <div class="Pagination-desktop">
      <ul class="Pagination-list page-numbers" role="list">
        @foreach ($pagination_desktop as $item)
          <li>
            {!! $item !!}
          </li>
        @endforeach
      </ul>
    </div>
    {{-- Mobile (shows fewer page numbers) --}}
    <div class="Pagination-mobile">
      <ul class="Pagination-list page-numbers" role="list">
        @foreach ($pagination_mobile as $item)
          <li>
            {!! $item !!}
          </li>
        @endforeach
      </ul>
    </div>
  </nav>
@endif
