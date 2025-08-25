{{-- Page header is used on both parent and child pages --}}
{{-- Note: Expects $post_parent, $excerpt, $image_id, and $image_alt arguments --}}
<div class="PageHeader l-padding {{ $post_parent ? 'is-child' : '' }}">
  <div class="PageHeader-wrap l-wrap">
    <div class="PageHeader-content">
      {{-- Optional breadcrumb to parent page --}}
      @if (!empty($post_parent))
        <a class="PageHeader-breadcrumb" href="{{ get_permalink($post_parent) }}">
          {{ get_the_title($post_parent) }}
        </a>
      @endif

      <h1 class="PageHeader-title f-title" id="title">
        {!! $title !!}
      </h1>

      @if ($excerpt)
        <p class="PageHeader-description">
          {!! App\nowrap($excerpt) !!}
        </p>
      @endif
    </div>{{-- end PageHeader-content --}}
    @if (isset($image_id))
      <div class="PageHeader-image">
        {!! App\img_tag($image_id, [
          'class' => '',
          'crop' => 'sixteen_nine',
          'sizes' => '100vw',// FIXME
        ]) !!}
      </div>{{-- end PageHeader-image --}}
    @endif
  </div>
</div><!-- end PageHeader -->
