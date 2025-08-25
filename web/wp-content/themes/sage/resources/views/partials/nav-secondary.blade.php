{{--
  Secondary Navigation
  $secondary_navigation (global) - a Navi object created from a WP Menu
--}}
<ul class="Nav-list Nav-secondary" role="list">
  @include('partials.nav-items', [
    'menu_items' => $secondary_navigation,
    'max_depth' => 0,
    'base_class' => 'Nav',
    'custom_class' => 'Nav-secondary',
  ])
  {{-- Search (desktop) --}}
  <li class="Nav-item Nav-secondary-item is-search hide-mobile">
    @include('partials.nav-search', [
      'id' => 'nav-search',
      'classes' => 'hide-mobile',
    ])
  </li>
</ul>
