{{-- Navigation (basic nav partial, used in footer)--}}
@if ($nav_menu)
  @php
    $base_class  = $base_class ?? 'Nav';
    $custom_class = !empty($custom_class) ? $base_class . '-' . $custom_class : false;
    $show_link_icons = isset($show_link_icons) ? $show_link_icons : true;
  @endphp
  <ul class="{{ $custom_class ? $custom_class.' ' : '' }}{{ $base_class }}-list" role="list">
    @include('partials.nav-items', [
      'menu_items' => $nav_menu,
      'max_depth' => $max_depth,
      'base_class' => $base_class,
      'custom_class' => $custom_class,
      'show_link_icons' => $show_link_icons,
    ])
  </ul>
@endif
