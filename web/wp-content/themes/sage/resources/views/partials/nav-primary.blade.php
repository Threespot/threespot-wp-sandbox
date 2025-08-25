{{--
  Primary Navigation
  $primary_navigation (global) - a Navi object created from a WP Menu
--}}
<ul class="Nav-list Nav-primary" role="list">
  @include('partials.nav-items', [
    'menu_items' => $primary_navigation,
    'max_depth' => 1,// supports children
    'base_class' => 'Nav',
    'custom_class' => 'Nav-primary',
  ])
  {{-- Desktop CTA links --}}
  {{-- Note: They had to be combined in a single <li> for layout purposes --}}
  @if (isset($acf_options) && $acf_options['donate_url'])
    <li class="Nav-item Nav-primary-item">
      <a class="Nav-cta btn-solid is-donate" href="{{ $acf_options['donate_url'] }}">Donate</a>
    </li>
  @endif
</ul>
