<!doctype html>
<html class="no-js smooth-scroll" {!! get_language_attributes() !!}>
  @include('partials.head')
  <body @php(body_class())>
    @php(wp_body_open())
    {{-- Google Tag Manager (noscript) --}}
    @if ($is_production && !empty($gtm_id))
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm_id }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @php(do_action('get_header'))
    @include('partials.skip-links')
    {{-- Header --}}
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
    {{-- Footer --}}
    @php(do_action('get_footer'))
    @include('partials.footer')
    @php(wp_footer())
    {{--
      Optional: Adobe Fonts tracking code (formerly Typekit)
      Since we’re hosting the fonts locally, we need to manually request their tracking file.
      - The tracking file URL is from the official CSS file (https://use.typekit.net/xgt4mvl.css)
      - AJAX request JS from https://plainjs.com/javascript/ajax/making-cors-ajax-get-requests-54/
      - JS was minified using https://skalman.github.io/UglifyJS-online/

      Below is the un-minified code:

      async function fetchCORS(url) {
        try {
          const response = await fetch(url, {
            method: 'GET',
            mode: 'cors'
          });

          if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
          }

          // Success, do nothing
          return response;
        } catch (error) {
          console.warn('Could not ping Adobe Fonts tracking URL');
          throw error;
        }
      }

      fetchCORS('{{ $adobe_fonts_url }}');
    --}}
    {{-- @if ($is_production && !empty($adobe_fonts_url))
      <script>async function fetchCORS(t){try{var o=await fetch(t,{method:"GET",mode:"cors"});if(o.ok)return o;throw new Error("Response status: "+o.status)}catch(t){throw console.warn("Could not ping Adobe Fonts tracking URL"),t}}fetchCORS("{{ $adobe_fonts_url }}");</script>
    @endif --}}
  </body>
</html>
