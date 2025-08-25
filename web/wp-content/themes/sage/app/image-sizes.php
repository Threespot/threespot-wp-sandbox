<?php
namespace App;

// Add custom image sizes that should be generated no matter what (e.g. thumbnail previews for ACF)
// If implementing ACF Image Crop plugin, use largest size of each aspect ratio instead.
add_image_size('sixteen_nine_thumb', 320, 180, true);

// Optional: Remove unused default WP image sizes (but always keep “thumbnail”)
// https://developer.wordpress.org/reference/hooks/intermediate_image_sizes_advanced/
// Note: Can get list of sizes using get_intermediate_image_sizes()
// add_filter('intermediate_image_sizes_advanced', function($sizes) {
//     unset($sizes['small']);// 150px
//     unset($sizes['medium']);// 300px
//     unset($sizes['medium_large']);// 768px
//     unset($sizes['large']);// 1024px
//     unset($sizes['1536x1536']);
//     unset($sizes['2048x2048']);
//     return $sizes;
// });

// TODO: Dynamically generate Fly image sizes using Imgix approach,
//       create custom helper to add “srcset” like on CompTIA and MilSpouse
//       https://gist.github.com/tedw/9232548766d5f9e8e517974a52b0d392

// TODO: Look into making all WP images dynamic
//       https://github.com/junaidbhura/fly-dynamic-image-resizer/issues/26

// TODO: Use native WP image functions instead of Fly.
//       There’s currently an issue when trying to output an image at an
//       aspect ratio other than its original one.
//       - https://developer.wordpress.org/reference/functions/wp_calculate_image_srcset/#comment-1369
//       - https://threespot.slack.com/archives/C04S5G4P4FR/p1713996576782319

// Add custom image sizes that will only be generated after the first request
//
// NOTE: These sizes won’t be available to users in the admin when embedding images.
// https://github.com/junaidbhura/fly-dynamic-image-resizer/wiki
//
//   {!! App\img_tag(123, [
//     'class' => 'example-img',// optional
//     'crop' => 'square',
//     'sizes' => '100vw',
//     'loading' => 'lazy',// optional
//     'fetchpriority' => 'high',// optional
//     'lazy_load' => true,// optional, replaces src/srcset with data-src/data-srcset
//   ]) !!}
//
// NOTE: Use double underscores in Fly image size names to prevent false positives
//       when using img_tag() helper function (e.g. “square” vs. “square_scaled”).
if (function_exists('fly_add_image_size')) {
  // Square
  fly_add_image_size('square__1', 360, 360, true);
  fly_add_image_size('square__2', 750, 750, true);
  fly_add_image_size('square__3', 1080, 1080, true);

  // Square, scaled not cropped
  fly_add_image_size('square_scaled__1', 360, 360, false);
  fly_add_image_size('square_scaled__2', 750, 750, false);
  fly_add_image_size('square_scaled__3', 1080, 1080, false);
  fly_add_image_size('square_scaled__4', 1280, 1280, false);

  // 16:9
  fly_add_image_size('sixteen_nine__1', 640, 360, true);
  fly_add_image_size('sixteen_nine__2', 960, 540, true);
  fly_add_image_size('sixteen_nine__3', 1200, 675, true);
  fly_add_image_size('sixteen_nine__4', 1600, 900, true);
  // fly_add_image_size('sixteen_nine__5', 1824, 1026, true);
  // fly_add_image_size('sixteen_nine__6', 2400, 1350, true);

  // 3:2
  // fly_add_image_size('three_two__1', 630, 420, true);
  // fly_add_image_size('three_two__2', 960, 640, true);
  // fly_add_image_size('three_two__3', 1200, 800, true);
  // fly_add_image_size('three_two__4', 1440, 960, true);
}
