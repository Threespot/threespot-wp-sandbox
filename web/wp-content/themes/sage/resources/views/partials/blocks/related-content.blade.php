{{-- Optional: Related content block --}}
{{-- https://github.com/Threespot/childrens-law-center/issues/49 --}}
@php
  $exclude_ids = [get_the_ID()];// exclude current page

  // ACF returns an empty string by default so we need
  // to change to an empty array so we can merge later.
  $manual_posts = empty($manual_posts) ? [] : $manual_posts;

  // Add any manual post IDs to our exclude list
  foreach ($manual_posts as $post) {
    $exclude_ids[] = $post->ID;
  }

  // Construct the custom query using get_posts()
  // https://developer.wordpress.org/reference/functions/get_posts/
  // Query arg docs: https://www.billerickson.net/code/wp_query-arguments/
  $query_args = [
    'post__not_in' => $exclude_ids,
    'numberposts' => intval($max_results) - count($manual_posts),
    'post_status' => 'publish',
    'post_type' => $post_types ?: get_post_type(),
  ];

  // Add taxonomy query
  if (!empty($taxonomy)) {
    $tax_query = [
      'relation' => 'OR'// default is “AND”
    ];

    foreach ($taxonomy as $name => $terms) {
      if (!empty($terms)) {
        $tax_query[] = [
          'taxonomy' => $name,
          'field' => 'term_id',
          'terms' => $terms,
        ];
      }
    }

    $query_args['tax_query'] = $tax_query;
  }

  // Don’t run query if we’re already at the max number of posts
  $related_posts = $query_args['numberposts'] > 0 ? get_posts($query_args) : [];
  // Set the fallback value as empty array so we can merge with manual posts
  $related_posts = empty($related_posts) ? [] : $related_posts;

  // Merge post arrays
  $posts = array_merge($manual_posts, $related_posts);
@endphp
@if (is_admin() || !empty($posts))
  <div class="RelatedContent {{ $block ? $block['classes'] : '' }}" @if($block) id="{{ $block['id']}}" @endif>
    @if ($heading)
      <h2 class="RelatedContent-heading">{!! $heading !!}</h2>
    @endif
    <ul class="RelatedContent-list Listing" role="list">
      @foreach ($posts as $post)
        @include('partials/listing-item', [
          'post_obj' => $post,
          'show_excerpts' => false,
        ])
      @endforeach
    </ul>
  </div>{{-- end RelatedContent --}}
@endif
