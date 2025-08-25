<!doctype html>
<html class="no-js" {!! get_language_attributes() !!}>
  @include('partials.head')
  <body @php body_class() @endphp>
    @include('partials.svg-sprite')
    {{-- Google Tag Manager (noscript) --}}
    @if ($is_production && !empty($gtm_id))
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm_id }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @php do_action('get_header') @endphp
    @include('partials.skip-links')
    @include('partials.header')
    <main class="Main" id="main">
      {{-- Login form for password-protected pages --}}
      @if ( post_password_required() )
        <div class="l-padding">
          <div class="l-wrap--narrow u-richtex">
            {!! get_the_password_form() !!}
          </div>
        </div>
      @else
        @yield('content')
      @endif
    </main>
    @php(do_action('get_footer'))
    @include('partials.footer')
    @php(wp_footer())

    {{--
      FIXME: This code requires an “adobe_fonts_url” ACF text field in Theme Settings
      Optional: Adobe Fonts tracking code (formerly Typekit)
      Since we’re hosting the fonts locally, we need to manually request their tracking file.
      - The tracking file URL is from the official CSS file (https://use.typekit.net/fcu1equ.css)
      - AJAX request JS from https://plainjs.com/javascript/ajax/making-cors-ajax-get-requests-54/
      - JS was minified using https://skalman.github.io/UglifyJS-online/

      Below is the un-minified code:

        function getCORS(url, success, failure) {
          var xhr = new XMLHttpRequest();
          if (!('withCredentials' in xhr)) xhr = new XDomainRequest(); // fix IE8/9
          xhr.open('GET', url);
          xhr.onload = success;
          xhr.addEventListener('error', failure);
          xhr.send();
          return xhr;
        }

        getCORS('{{ $adobe_fonts_url }}', function(request) {
          // Success, do nothing
        }, function(error) {
          console.warn('Could not ping Adobe Fonts tracking URL');
        });
    --}}
    {{-- @if ($is_production && !empty($adobe_fonts_url))
      <script>function getCORS(n,e,t){var o=new XMLHttpRequest;return(o="withCredentials"in o?o:new XDomainRequest).open("GET",n),o.onload=e,o.addEventListener("error",t),o.send(),o}getCORS("{{ $adobe_fonts_url }}",function(n){},function(n){console.warn("Could not ping Adobe Fonts tracking URL")});</script>
    @endif --}}
  </body>
</html>
