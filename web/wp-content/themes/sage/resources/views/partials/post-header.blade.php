{{-- Post header --}}
@php
  $post_id = $post->ID;
  $post_type = $post->post_type;
  // $custom_post_type = App\get_custom_post_type($post_id);
  $image_id = get_post_thumbnail_id($post_id);
@endphp
<div class="PostHeader l-padding">
  <div class="PostHeader-wrap l-wrap">
    <div class="PostHeader-content">
      {{-- Post type --}}
      {{-- @if ($custom_post_type && !empty($custom_post_type['title']))
        @if (!empty($custom_post_type['url']))
          <a class="PostHeader-type is-link"
            href="{{ $term['url'] }}">
            {!! $custom_post_type['title'] !!}
          </a>
        @else
          <p class="PostHeader-type">
            {!! $custom_post_type['title'] !!}
          </p>
        @endif
      @endif --}}

      <h1 class="PostHeader-title f-title" id="title">
        {!! $title !!}
      </h1>

      <div class="PostHeader-meta">
        {{-- Author --}}
        @if ($author_id = get_field('author_person_id'))
          <a class="PostHeader-meta-title is-author" href="{{ get_permalink($author_id) }}">
            {!! App\append_icon([
              'text' => get_the_title($author_id),
              'class' => 'u-nowrap',
              'svg' => [
                'file' => 'chev-right',
                'class' => 'icon',
                'width' => 15,
                'sprite' => true,
              ]
            ]) !!}
          </a>
        @endif
        {{-- Date (news, resources) --}}
        @if ($post_type == 'news')
          <p class="PostHeader-meta-text is-date">
            {{ get_the_date("F j, Y", $post_id) }}
          </p>
        {{-- Date (event) --}}
        @elseif ($post_type == 'event' && $event_date_text = get_field('event_date'))
          @php
            // Remove prepositions so strtotime() can convert to a timestamp
            $clean_event_date = preg_replace('/\b(at|from|to)\b/', '', $event_date_text);
            // Note: The timestamp strtotime() returns doesn’t take timezones into account
            // https://www.php.net/manual/en/function.strtotime.php
            $event_timestamp = strtotime($clean_event_date);
          @endphp
          {{-- Use <time> if the date string can be parsed --}}
          @if ($event_timestamp)
            <time class="PostHeader-meta-text is-date" datetime="{{ date('Y-m-d', $event_timestamp) }}">
              {{ $event_date_text }}
            </time>
          @else
            <p class="PostHeader-meta-text is-date">{{ $event_date_text }}</p>
          @endif
        @elseif ($post_type == 'person')
          {{-- Job title --}}
          @if ($job_title = get_field('job_title'))
            <p class="PostHeader-meta-title is-job">{!! $job_title !!}</p>
          @endif
          {{-- Email --}}
          @if ($email = get_field('email'))
            <a class="PostHeader-meta-text is-email" href="{!! App\obfuscate('mailto:'.$email) !!}">
              {!! App\obfuscate($email) !!}
            </a>
          @endif
        @endif
      </div>{{-- end PostHeader-meta --}}
    </div>{{-- end PostHeader-content --}}
    @if ($image_id)
      <div class="PostHeader-image">
        {{-- Example of using a custom aspect ratio for a specific post type --}}
        @if ($post_type == 'person')
          {!! App\img_tag($image_id, [
            'class' => '',
            'crop' => 'square',
            'sizes' => '100vw, FIXME',
            'alt' => App\get_img_alt($image_id),
            ]) !!}
        @else
          {!! App\img_tag($image_id, [
            'class' => '',
            'crop' => 'sixteen_nine',
            'sizes' => '100vw, FIXME',
            'alt' => App\get_img_alt($image_id),
            ]) !!}
        @endif
      </div>{{-- end PostHeader-image --}}
    @endif
  </div>
</div><!-- end PostHeader -->
