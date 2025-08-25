{{--
  Title: Related Content
  Description: Customizable list of related content
  Category: threespotblock
  Icon: <svg width="24" height="24" viewBox="0 0 32 30" role="img" aria-hidden="true" focusable="false"><path d="M0 0v6h10V0H0zm14 0v2h18V0H14zm0 4v2h14V4H14zM0 12v6h10v-6H0zm14 0v2h18v-2H14zm0 4v2h14v-2H14zM0 24v6h10v-6H0zm14 0v2h18v-2H14zm0 4v2h14v-2H14z"/></svg>
  Keywords: content list related
  Mode: preview
  PostTypes: page post career event news person resource story
  SupportsAlign: false
  SupportsAnchor: true
  SupportsMultiple: true
--}}
{{--
  Example specs:
  https://github.com/Threespot/childrens-law-center/issues/49

  Example field groups:
  https://live-childrens-law-center.pantheonsite.io/wp/wp-admin/post.php?post=417&action=edit
--}}
@php
  $heading = get_field('heading');
  $post_types = get_field('post_types');
  $taxonomy = get_field('taxonomy');
  $manual_posts = get_field('manual_posts');
  $max_results = get_field('max_results');
@endphp
@include('partials.blocks.related-content', [
  'heading' => $heading,
  'post_types' => $post_types,
  'taxonomy' => $taxonomy,
  'manual_posts' => $manual_posts,
  'max_results' => $max_results,
])
