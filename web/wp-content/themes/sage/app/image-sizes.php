<?php
namespace App;

// Add custom image sizes that should be generated no matter what (e.g. thumbnail previews for ACF)
// If implementing ACF Image Crop plugin, use largest size of each aspect ratio instead.
add_image_size('sixteen_nine_thumb', 320, 180, true);

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
  fly_add_image_size('sixteen_nine__1', 750, 422, true);
  fly_add_image_size('sixteen_nine__2', 1024, 576, true);
  fly_add_image_size('sixteen_nine__3', 1280, 720, true);
  fly_add_image_size('sixteen_nine__4', 1440, 810, true);
  fly_add_image_size('sixteen_nine__5', 1600, 900, true);
  fly_add_image_size('sixteen_nine__6', 1920, 1080, true);
  fly_add_image_size('sixteen_nine__7', 2160, 1215, true);

  // 3:2 (for cards)
  // fly_add_image_size('three_two__1', 366, 244, true);
  // fly_add_image_size('three_two__2', 580, 387, true);
  // fly_add_image_size('three_two__3', 752, 501, true);
  // fly_add_image_size('three_two__3', 900, 600, true);
  // fly_add_image_size('three_two__4', 1288, 859, true);

}
