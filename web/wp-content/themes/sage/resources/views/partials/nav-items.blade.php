{{--
  Generate nav menu item markup
  $max_depth defines how many levels deep the menu supports (defaults to 0)
--}}
@php
  $max_depth = $max_depth ?: 0;
  $current_depth = $current_depth ?? 0;
  $show_link_icons = isset($show_link_icons) ? $show_link_icons : true;
@endphp

@if ($menu_items && $current_depth <= $max_depth)
  @php
    // Append “sublist” to child item classes
    $base_class = $current_depth > 0 ? $base_class . '-sublist' : $base_class;
    $custom_class = $custom_class && $current_depth > 0 ? $custom_class . '-sublist' : $custom_class;
  @endphp
  @foreach ($menu_items as $item)
    @php
      $has_menu = $item->children && $current_depth < $max_depth;
    @endphp
    <li class="{{ $base_class.'-item' }}{{ !empty($custom_class) ? ' '.$custom_class.'-item' : '' }}{{ $has_menu ? ' has-menu' : '' }}{{ ' level-'.$current_depth }}">
      @if ($has_menu)
        {{-- Toggle button with submenu --}}
        @php
          $menu_id = sanitize_title($item->label);
        @endphp
        <a class="{{ $base_class.'-toggle' }}{{ !empty($custom_class) ? ' '.$custom_class.'-link' : '' }}{{ ' level-'.$current_depth }}"
          href="{{ $item->url }}"
          data-expands="{{ $menu_id }}"
          data-expands-height
          aria-label="{!! $item->label !!}">
          {{-- Wrapper <span> required for layout purposes --}}
          <span class="Nav-toggle-text">
            {!! App\append_icon([
              'text' => $item->label,
              'class' => 'Nav-toggle-lastWord u-nowrap',
              'svg' => [
                'file' => 'icons/chev-down',
                'class' => 'icon',
                'width' => 11,
                'sprite' => true,
              ]
            ]) !!}
          </span>
        </a>
        {{-- Note: This wrapper div is required to achieve the desktop nav layout --}}
        <div class="{{ $base_class.'-sublist' }}{{ !empty($custom_class) ? ' '.$custom_class.'-sublist' : '' }}{{ ' level-'.$current_depth }}" id="{{ $menu_id }}">
          <div class="{{ $base_class.'-sublist-wrap' }}">
            <ul class="{{ $base_class.'-sublist-list' }}{{ !empty($custom_class) ? ' '.$custom_class.'-sublist-list' : '' }}" role="list">
              {{-- Check if parent link should be added to the submenu --}}
              @if (get_field('add_to_submenu', $item->id))
                <li class="{{ $base_class.'-sublist-item' }}{{ !empty($custom_class) ? ' '.$custom_class.'-sublist-item' : '' }}{{ ' level-'.$current_depth + 1 }} no-js-hide">
                  <a class="{{ $base_class.'-sublist-link' }}{{ !empty($custom_class) ? ' '.$custom_class.'-sublist-link' : '' }}{{ ' level-'.$current_depth + 1 }}{{ $item->active ? ' is-current' : '' }} is-heading"
                    href="{{ $item->url }}"
                    {!! $item->active ? 'aria-current="page"' : '' !!}>
                    @if ($show_link_icons)
                      {!! App\append_icon([
                        'text' => get_field('custom_text', $item->id) ?: $item->label,
                        'class' => 'u-nowrap',
                        'svg' => [
                          'file' => 'icons/chev-right',
                          'class' => 'icon',
                          'width' => 8,
                          'sprite' => true,
                        ]
                      ]) !!}
                    @else
                      {!! get_field('custom_text', $item->id) ?: $item->label !!}
                    @endif
                  </a>
                </li>
              @endif
              {{-- Recursively include child items --}}
              @include('partials.nav-items', [
                'menu_items' => $item->children,
                'current_depth' => $current_depth + 1,
              ])
            </ul>
          </div>
        </div>{{-- end Nav-menuWrapper --}}
      @else
        {{-- Single link, no submenu --}}
        <a class="{{ $base_class.'-link' }}{{ !empty($custom_class) ? ' '.$custom_class.'-link' : '' }}{{ $item->active ? ' is-current' : '' }}{{ ' level-'.$current_depth }}"
          href="{{ $item->url }}"
          {!! $item->active ? 'aria-current="page"' : '' !!}>
          @if ($show_link_icons)
            {!! App\append_icon([
              'text' => $item->label,
              'class' => 'u-nowrap',
              'svg' => [
                'file' => 'icons/chev-right',
                'class' => 'icon',
                'width' => 8,
                'sprite' => true,
              ]
            ]) !!}
          @else
            {!! $item->label !!}
          @endif
        </a>
      @endif
    </li>
  @endforeach
@endif
