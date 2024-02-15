//------------------------------------------------------------------------
// Custom block editor overrides
// https://developer.wordpress.org/block-editor/developers/filters/block-filters/
// https://soderlind.no/hide-block-styles-in-gutenberg/
// https://www.billerickson.net/block-styles-in-gutenberg/
// https://wpdevelopment.courses/a-list-of-all-default-gutenberg-blocks-in-wordpress-5-0/
//------------------------------------------------------------------------

// NOTE: As of WP 5.9 the reset styles use :where(), resulting in few if any specificity issues.
//       Only uncomment the code below if you run into issues.
//
// Remove WP reset styles from the dynamically-generated admin stylesheet, load-styles.php (WP 5.8)
//
// Note: The href contains all of the individual partials that are concatenated into a single CSS file.
//       All we have to do is remove “wp-reset-editor-styles” from the href. However, updating the
//       href will cause a long FOUC, so we’re adding a new stylesheet and then disabling
//       the original once the new one has loaded.
//
// Note: We can ignore <link> tags with IDs since this one doesn’t have one.
// let linkTags = document.querySelectorAll('link[rel="stylesheet"]:not([id])');
// // There should only be one matching link tag but we’re using forEach() just to be safe.
// linkTags.forEach((tag) => {
//   let href = tag.getAttribute('href');
//   if (href.indexOf('load-styles.php') > -1) {
//     // Create new stylesheet without the “wp-reset-editor-styles” styles
//     // You can preview those styles using this URL:
//     // /wp/wp-admin/load-styles.php?c=1&dir=ltr&load%5Bchunk_1%5D=wp-reset-editor-styles&ver=5.9.1
//     let link = document.createElement('link');
//     link.media = 'all';
//     link.rel = 'stylesheet';
//     link.type = 'text/css';
//     link.href = href.replace('wp-reset-editor-styles,', '');
//     // Disable the original stylesheet on load
//     link.onload = function() {
//       tag.disabled = true;
//     };
//     // NOTE: We’re adding this stylehseet right after the original link
//     //       tag to avoid specificity issues.
//     tag.after(link);
//   }
// });

// NOTE: The code below is only necessary in WP 5.7 and below.
//
// Remove inline <style> tags that normalize the editor content to
// prevent conflicts with our custom theme styles (WP 5.7 and below).
// window.addEventListener('load', (event) => {
//   let styleTagsBody = document.querySelectorAll('body style');
//   styleTagsBody.forEach((tag) => {
//     if (
//       tag.textContent.indexOf('.editor-styles-wrapper') == 0 ||
//       tag.textContent.indexOf('.editor-styles-wrapper h2 {') > -1
//     ) {
//       tag.innerHTML = '';
//     }
//   });
// });

// Remove default panels from the side if not used
// https://wordpress.stackexchange.com/questions/339436/removing-panels-meta-boxes-in-the-block-editor
// wp.data.dispatch('core/edit-post').removeEditorPanel('discussion-panel');
// wp.data.dispatch('core/edit-post').removeEditorPanel('featured-image');
// wp.data.dispatch('core/edit-post').removeEditorPanel('page-attributes');
// wp.data.dispatch('core/edit-post').removeEditorPanel('post-excerpt');
// wp.data.dispatch('core/edit-post').removeEditorPanel('post-link');
// wp.data.dispatch('core/edit-post').removeEditorPanel('taxonomy-panel-category');
// wp.data.dispatch('core/edit-post').removeEditorPanel('taxonomy-panel-post_tag');
// wp.data.dispatch('core/edit-post').removeEditorPanel('taxonomy-panel-TAXONOMY-NAME');
// wp.data.dispatch('core/edit-post').removeEditorPanel('template');

// OPTIONAL: Only show the new “Templates” metabox (WP 5.9) for specific post types
// const showTemplateTypes = ['page'];
//
// // Get the current post type
// // https://github.com/WordPress/gutenberg/issues/25330
// // https://stackoverflow.com/a/60907141/673457
// const getPostType = () => wp.data.select('core/editor').getCurrentPostType();
//
// // Initial vlaue
// let postType = null;
//
// // This will run asynchronously each time page data is pulled via AJAX
// wp.data.subscribe(() => {
//   const currentPostType = getPostType();
//   // Update only if a new value is returned
//   if (currentPostType !== postType) {
//     postType = currentPostType;
//     // Hide the Template metabox for all post types except ones in “showTemplateTypes”
//     if (!showTemplateTypes.includes(postType)) {
//       wp.data.dispatch('core/edit-post').removeEditorPanel('template');
//     }
//   }
// });

// NOTE: If this script doesn’t seem to be working, try switching to window load:
// window.addEventListener('load', () => {
// See https://github.com/WordPress/gutenberg/issues/25330
wp.domReady(() => {
  // NOTE: We reccommend using the Block Manager plugin to manage default block support
  // https://wordpress.org/plugins/block-manager/

  // Restrict block categories
  const restrictedCategories = [
    'yoast-internal-linking-blocks',
    'yoast-structured-data-blocks',
    // 'filebird/block-filebird-gallery'
  ];

  // Restrict individual blocks
  const restrictedBlocks = [];

  // Allow list will override restricted blocks above
  const allowedBlocks = [];

  wp.blocks.getBlockTypes().forEach(blockType => {
    let isCategoryRestricted = restrictedCategories.indexOf(blockType.category) > -1;
    let isBlockRestricted = restrictedBlocks.indexOf(blockType.name) > -1;
    let isBlockAllowed = allowedBlocks.indexOf(blockType.name) > -1;

    // console.log({
    //   category: blockType.category,
    //   name: blockType.name,
    //   isCategoryRestricted: isCategoryRestricted,
    //   isBlockRestricted: isBlockRestricted,
    //   isBlockAllowed: isBlockAllowed,
    //   unregistered: (isCategoryRestricted || isBlockRestricted) && !isBlockAllowed,
    // });

    // Unregister blocks
    if ((isCategoryRestricted || isBlockRestricted) && !isBlockAllowed) {
      wp.blocks.unregisterBlockType(blockType.name);
    }
  });

  // Unregister “core/embed” block variations
  // https://wordpress.stackexchange.com/a/379613/185703
  //
  // NOTE: We’re using the Disable Embeds plugin to do this instead.
  //       https://wordpress.org/plugins/disable-embeds/
  //
  // const restrictedEmbeds = [
  //   'wordpress',
  //   'wordpress-tv',
  // ];
  // Full list: animoto, cloudup, collegehumor, crowdsignal, dailymotion, facebook, flickr, imgur, instagram, issuu, kickstarter, meetup-com, mixcloud, reddit, reverbnation, screencast, scribd, slideshare, smugmug, soundcloud, speaker-deck, spotify, ted, tiktok, tumblr, twitter, videopress, vimeo, wordpress, wordpress-tv, youtube
  // wp.blocks.getBlockVariations('core/embed').forEach(blockVariation => {
  //   if (restrictedEmbeds.indexOf(blockVariation.name) > -1) {
  //     wp.blocks.unregisterBlockVariation('core/embed', blockVariation.name);
  //   }
  // });

  // Add custom classes to top-level wrappers of default blocks (see setup.php for child wrappers)
  // https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#blocks-getblockdefaultclassname
  // https://poolghost.com/rename-class-names-in-gutenberg-blocks/
  //------------------------------------------------------------------------
  wp.hooks.addFilter(
    'blocks.getBlockDefaultClassName',
    'threespot/set-block-custom-class-name',
    (className, blockName) => {
      // Add “u-richtext” class to blocks that support rich text
      // Note: The media block requires adding “u-richtext” to a child div
      //       so we’re doing that in setup.php using add_filter('render_block')
      let richtextBlocks = [
        'core/column'
      ];
      if (richtextBlocks.includes(blockName)) {
        return className + ' u-richtext';
      }

      // Add “l-block” class to add the same amount of vert margin as custom blocks
      let marginBlocks = [
        'core/cover',
        'core/embed',
        'core/gallery',
        'core/group',
        'core/media-text',
        // 'core/pullquote',
        // 'core/quote'
      ];
      if (marginBlocks.includes(blockName)) {
        return className + ' l-block';
      }

      return className;
    }
  );

  // Remove unused default block styles
  //------------------------------------------------------------------------
  // Image
  wp.blocks.unregisterBlockStyle('core/image', 'circle-mask');
  wp.blocks.unregisterBlockStyle('core/image', 'rounded');

  // Button
  // wp.blocks.unregisterBlockStyle('core/button', 'outline');
  wp.blocks.unregisterBlockStyle('core/button', 'squared');

  // Quote
  wp.blocks.unregisterBlockStyle('core/quote', 'large');

  // Pullpuote
  wp.blocks.unregisterBlockStyle('core/pullquote', 'solid');
  wp.blocks.unregisterBlockStyle('core/pullquote', 'solid-color');

  // Table
  wp.blocks.unregisterBlockStyle('core/table', 'stripes');

  // Separator
  wp.blocks.unregisterBlockStyle('core/separator', 'wide');
  wp.blocks.unregisterBlockStyle('core/separator', 'dots');

  // Add project-specific custom block styles
  //------------------------------------------------------------------------
  wp.blocks.registerBlockStyle('core/paragraph', {
    name: 'intro',
    label: 'Intro text',
  });

  wp.blocks.registerBlockStyle('core/columns', {
    name: 'equal-height',
    label: 'Equal Heights',
  });

  wp.blocks.registerBlockStyle('core/gallery', {
    name: 'align-bottom',
    label: 'Bottom Aligned',
  });

  wp.blocks.registerBlockStyle('core/gallery', {
    name: 'align-center',
    label: 'Center Aligned',
  });

  // Optional: Logo grid gallery
  // wp.blocks.registerBlockStyle('core/gallery', {
  //   name: 'logo-grid',
  //   label: 'Logo Grid',
  // });

  wp.blocks.registerBlockStyle('core/quote', {
    name: 'no-quotes',
    label: 'No quotes',
  });

  // Optional: Max-height image
  // wp.blocks.registerBlockStyle('core/image', {
  //   name: 'max-height',
  //   label: 'Crop height',
  // });

  // Optional: Dark background group
  // wp.blocks.registerBlockStyle('core/group', {
  //   name: 'bg-dark',
  //   label: 'Dark background',
  // });

  //------------------------------------------------------------------------
  // Configure TinyMCE
  //------------------------------------------------------------------------
  /* global acf */
  /* eslint no-unused-vars: "off" */
  if (typeof acf !== 'undefined') {
    acf.add_filter('wysiwyg_tinymce_settings', function(mceInit, id, field) {
      // Strip Gutenberg block magic comments to avoid breaking
      // ACF wysiwyg fields when copying and pasting block text.
      // Specifically, the curly braces break ACF wysiwyg fields:
      // e.g. <!-- wp:heading {"level":3} -->
      // https://www.advancedcustomfields.com/resources/javascript-api/#filters-wysiwyg_tinymce_settings
      // https://www.tiny.cloud/docs/plugins/paste/#paste_preprocess
      // RegEx from https://stackoverflow.com/a/29194283/673457
      mceInit.paste_preprocess = function(plugin, args) {
        var pattern = /(?=<!--)([\s\S]*?)-->(\n\n+)?/g;
        args.content = args.content.replace(pattern, '');
      };

      // Remove H1 from list of headings
      mceInit.block_formats = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre';

      // Override custom font-family options
      // mceInit.font_formats = '-apple-system,BlinkMacSystemFont,Segoe UI,X-LocaleSpecific,sans-serif';

      // Override custom font-size options
      // mceInit.fontsize_formats = '1em';

      // Extend TinyMCE’s list of valid HTML element
      // https://www.tiny.cloud/docs-3x/reference/configuration/Configuration3x@extended_valid_elements/
      // https://www.isitwp.com/allow-more-html-tags-in-the-editor/
      // extHtml = 'iframe[name|width|height|frameborder|scrolling|src],script';
      //
      // if (mceInit.extended_valid_elements) {
      //   mceInit.extended_valid_elements += ',' + extHtml;
      // } else {
      //   mceInit.extended_valid_elements = extHtml;
      // }

      return mceInit;
    });
  }

});
