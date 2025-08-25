<header class="Header l-padding">
  <div class="Header-wrap l-wrap">
    {{-- Wrap logo in <h1> only on the homepage --}}
    <{!! is_front_page() ? 'h1' : 'div' !!} class="Header-logo">
      <a class="Header-logo-link" href="{{ home_url('/') }}">
        <span class="u-screenreader">{{ $site_name }}</span>
        {!! App\svg([
          'file' => 'logo-fixme',
          'class' => 'Header-logo-image',
          'width' => 150,
        ]) !!}
      </a>
    </{!! is_front_page() ? 'h1' : 'div' !!}>

    {{-- Use “aria-label” to differentiate between site navs --}}
    <nav class="Header-nav Nav" id="nav" role="navigation" aria-label="Main">
      {{-- Search (mobile) --}}
      @include('partials.nav-search', [
        'id' => 'nav-search-mobile',
        'classes' => 'hide-desktop'
      ])
      {{-- Primary nav --}}
      @includeWhen($primary_navigation, 'partials.nav-primary')
      {{-- Secondary nav --}}
      @includeWhen($secondary_navigation, 'partials.nav-secondary')
    </nav>

  </div><!-- end Header-wrap -->
</header>
