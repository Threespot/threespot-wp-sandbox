//------------------------------------------------------------------------
// Custom block editor overrides
// https://developer.wordpress.org/block-editor/developers/filters/block-filters/
// https://soderlind.no/hide-block-styles-in-gutenberg/
// https://www.billerickson.net/block-styles-in-gutenberg/
// https://wpdevelopment.courses/a-list-of-all-default-gutenberg-blocks-in-wordpress-5-0/
//------------------------------------------------------------------------
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

// NOTE: If this script doesn’t seem to be working, try switching to native events:
// - window.addEventListener('load', () => {…})
// - document.addEventListener('DOMContentLoaded', () => {…})
// See https://github.com/WordPress/gutenberg/issues/25330
// Alternatively, you can try importing domReady difectly
// import domReady from '@wordpress/dom-ready';
// domReady(() => {…});

wp.domReady(() => {
  // NOTE: We reccommend using the Block Manager plugin to manage default block support
  // https://wordpress.org/plugins/block-manager/

  // Block config that can’t be done via theme.json
  wp.blocks.getBlockTypes().forEach((blockType) => {
    // Disable wide/fullwidth alignment options for headings
    if (blockType.name == 'core/heading') {
      blockType.supports.align = [];
    }

    // SearchWP adds a block that can’t be removed with the Block Manager plugin
    // so we have to manually unregister it.
    if (blockType.category == 'searchwp') {
      wp.blocks.unregisterBlockType(blockType.name);
    }
  });

  // Add custom classes to top-level wrappers of default blocks (see setup.php for child wrappers)
  // https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#blocks-getblockdefaultclassname
  // https://poolghost.com/rename-class-names-in-gutenberg-blocks/
  //------------------------------------------------------------------------
  wp.hooks.addFilter(
    'blocks.getBlockDefaultClassName',
    'threespot/set-block-custom-class-name',
    (className, blockName) => {
      let newClasses = '';

      // Add “u-richtext” class to blocks that support rich text
      // Note: The Media & Text and Cover blocks requires adding “u-richtext” to a child div
      //       so we’re doing that in setup.php using add_filter('render_block')
      let richtextBlocks = [
        'core/column',
        'core/details',
        'core/group',
      ];
      if (richtextBlocks.includes(blockName)) {
        return className + ' u-richtext';
      }

      return className;
    }
  );

  // NOTE: Removing unused default block styles & variations is done in block-config.php

  // Add project-specific block styles
  //------------------------------------------------------------------------
  wp.blocks.registerBlockStyle('core/button', {
    name: 'teal',
    label: 'Teal',
  });

  wp.blocks.registerBlockStyle('core/button', {
    name: 'outline',
    label: 'Outline',
  });

  wp.blocks.registerBlockStyle('core/button', {
    name: 'link',
    label: 'Link',
  });

  wp.blocks.registerBlockStyle('core/columns', {
    name: 'no-col-gap',
    label: 'No Gutter',
  });

  // wp.blocks.registerBlockStyle('core/gallery', {
  //   name: 'align-bottom',
  //   label: 'Bottom Aligned',
  // });
  //
  // wp.blocks.registerBlockStyle('core/gallery', {
  //   name: 'align-center',
  //   label: 'Center Aligned',
  // });

  wp.blocks.registerBlockStyle('core/gallery', {
    name: 'logo-grid',
    label: 'Logo Grid',
  });

  wp.blocks.registerBlockStyle('core/group', {
    name: 'no-vert-margin',
    label: 'No Margin',
  });

  wp.blocks.registerBlockStyle('core/heading', {
    name: 'h2',
    label: 'H2',
  });

  wp.blocks.registerBlockStyle('core/heading', {
    name: 'h3',
    label: 'H3',
  });

  wp.blocks.registerBlockStyle('core/heading', {
    name: 'h4',
    label: 'H4',
  });

  wp.blocks.registerBlockStyle('core/heading', {
    name: 'h5',
    label: 'H5',
  });

  wp.blocks.registerBlockStyle('core/heading', {
    name: 'h6',
    label: 'H6',
  });

  wp.blocks.registerBlockStyle('core/image', {
    name: 'max-height',
    label: 'Max Height',
  });

  wp.blocks.registerBlockStyle('core/image', {
    name: 'outside-text',
    label: 'Outside Text',
  });

  wp.blocks.registerBlockStyle('core/list', {
    name: 'col-2',
    label: '2 Columns',
  });

  wp.blocks.registerBlockStyle('core/list', {
    name: 'col-3',
    label: '3 Columns',
  });

  wp.blocks.registerBlockStyle('core/list', {
    name: 'col-4',
    label: '4 Columns',
  });

  wp.blocks.registerBlockStyle('core/paragraph', {
    name: 'large',
    label: 'Large',
  });

  wp.blocks.registerBlockStyle('core/pullquote', {
    name: 'no-quotes',
    label: 'No Quotes',
  });

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

      // Hide menu bar (i.e. File, Edit, View, Format)
      mceInit.menubar = false;

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
