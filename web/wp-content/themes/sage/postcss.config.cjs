const postcssPxtoremConfig = require('./.pxtorem-config.cjs');

module.exports = {
  parser: 'postcss-safe-parser',
  plugins: {
    // Convert px to rem
    // https://github.com/cuth/postcss-pxtorem
    'postcss-pxtorem': postcssPxtoremConfig,
    // CSS polyfill and browser prefix config
    // https://github.com/csstools/postcss-plugins/tree/main/plugin-packs/postcss-preset-env
    'postcss-preset-env': {
      // Tell PostCSS to not alter the following selectors
      // https://github.com/csstools/postcss-plugins/blob/main/plugin-packs/postcss-preset-env/FEATURES.md
      features: {
        'any-link-pseudo-class': false,
        'custom-properties': false,
        'focus-visible-pseudo-class': false,
        'focus-within-pseudo-class': false,
        'has-pseudo-class': false,
        'is-pseudo-class': false,
        'logical-properties-and-values': false,
      },
      preserve: true,// allows plugins to keep original “pre-polyfilled” CSS
    },
  },
};
