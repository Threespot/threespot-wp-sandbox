<footer class="Footer l-padding" id="footer">
  <div class="l-wrap--wide">

    <div class="Footer-logo">
      <a class="Footer-logo-link" href="{{ home_url('/') }}">
        <span class="u-screenreader">{{ $site_name }}</span>
        {!! App\svg([
          'file' => 'logo-fixme',
          'class' => 'Footer-logo-image',
          'width' => 270,
        ])!!}
      </a>
    </div>{{-- end Footer-logo --}}

    {{-- Footer sitemap --}}
    <div class="Footer-sitemap">
      @include('partials.nav', [
        'nav_menu' => $footer_navigation,
        'max_depth' => 0,// top-level items only, no submenus
        'base_class' => 'Footer-sitemap',
        'show_link_icons' => false,
      ])
    </div>

    {{-- Social media links --}}
    @if(!empty($social_links))
      <ul class="Footer-social" role="list">
        @foreach($social_links as $item)
          {{-- NOTE: Empty repeater field rows will return false --}}
          @if(array_key_exists('name', $item))
            <li class="Footer-social-item">
              <a class="Footer-social-link"
                href="{{ $item['link'] }}"
                target="_blank" rel="noopener">
                <span class="u-screenreader">{{ $item['name'] }}</span>
                {!! App\svg([
                  'file_id' => $item['icon'],
                  'class' => 'icon',
                  'width' => $item['icon_width'] ?? 18
                ]) !!}
              </a>
            </li>
          @endif
        @endforeach
      </ul>
    @endif

    <p class="Footer-copyright">Copyright &copy; {{ date('Y') }} {!! $site_name !!}</p>

  </div>
</footer>
