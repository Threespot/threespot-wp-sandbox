@php
  $post_id = $post_obj->ID;
  $post_classes = implode(' ', get_post_class($post_id));
  $post_type = $post_type ?? get_post_type($post_id);
  $post_labels = App\get_custom_post_labels($post_id);
  $post_title = $post_obj->post_title;
  $post_date = get_the_date('F j, Y', $post_id);
  $post_image = get_post_thumbnail_id($post_id);
  $post_link = get_the_permalink($post_id);
  $post_excerpt = get_the_excerpt($post_id);
  // Can define custom heading level when including this partial, defaults to h2
  $heading_level = isset($heading_level) && is_int($heading_level) && $heading_level > 1 && $heading_level <= 6 ? $heading_level : 2;
  $show_images = isset($show_images) ? $show_images : true;
@endphp
<li class="Listing-item {{ $post_classes }} {{ $post_type }} {{ isset($classes) ? $classes : '' }}">
  <article class="Listing-item-wrap">
    <div class="Listing-text">
      {{-- Post type/taxonomy labels --}}
      @if (!empty($post_labels))
        <p class="Listing-labels">
          @foreach ($post_labels as $key => $label)
            @if (!empty($label['title']))
              @if ($key == 'primary' && !empty($label['url']))
                <a class="Listing-labels-text is-link is-{{ $key }}"
                  href="{{ $label['url'] }}">
                  {!! $label['title'] !!}
                </a>
              @else
                <span class="Listing-labels-text is-{{ $key }}">
                  {!! $label['title'] !!}
                </span>
              @endif
            @endif
          @endforeach
        </p>
      @endif

      {{-- Title --}}
      <h{{ $heading_level }} class="Listing-title">
        <a class="Listing-title-link" href="{{ $post_link }}">
          {!! $post_title !!}
        </a>
      </h{{ $heading_level }}>

      {{-- Excerpt --}}
      @if ($show_excerpts && $post_excerpt)
        <p class="Listing-excerpt">{!! $post_excerpt !!}</p>
      @endif

      {{-- Note: Could consider using <footer> if helpful for screen-reader users --}}
      @if (in_array($post_type, ['news', 'post', 'event']))
        <div class="Listing-meta">
          {{-- Date (shown for news and posts, though posts aren’t currently used) --}}
          @if ($post_date && in_array($post_type, ['news', 'post']))
            <time class="Listing-meta-date" datetime="{{ get_the_date('Y-m-d', $post_id) }}">
              {{ $post_date }}
            </time>
          {{-- Events have a custom plain-text field for the date --}}
          @elseif ($post_type == 'event')
            @php
              $event_date_text = get_field('event_date', $post_id) ?: $post_date;
              // Note: strtotime() doesn’t take timezones into account
              // https://www.php.net/manual/en/function.strtotime.php
              $event_timestamp = strtotime($event_date_text);
            @endphp
            {{-- Don’t use <time> if the date string can’t be parsed --}}
            @if (!$event_timestamp)
              <p class="Listing-meta-date">{{ $event_date_text }}</p>
            @else
              <time class="Listing-meta-date" datetime="{{ date('Y-m-d', $event_timestamp) }}">
                {{ $event_date_text }}
              </time>
            @endif
          @endif
          {{-- Author --}}
          @if ($author_fullname)
            <span class="Listing-meta-author">by
              @if($author_url)
                <a href="{{ $author_url }}">{{ $author_fullname }}</a>
              @else
                {{ $author_fullname }}
              @endif
            </span>
          @endif
        </div>{{-- end Listing-meta --}}
      @endif
    </div>{{-- end Listing-text --}}

    {{-- Image thumbnail --}}
    @if ($show_images && $post_image)
      <a class="Listing-thumbnail"
        href="{{ $post_link }}"
        tabindex="-1"
        aria-hidden="true">
          {!! App\img_tag($post_image, [
            'class' => 'Listing-thumbnail-image',
            'crop' => 'sixteen_nine',
            'sizes' => '(max-width: 480px) 100vw, (max-width: 900px) 30vw, 253px',
            'loading' => 'lazy',
            ]) !!}
      </a>
    @endif
  </article>
</li>
