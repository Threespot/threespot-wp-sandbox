/* eslint-disable */
module.exports = (api) => {
  // Add CSS variable fallbacks
  // https://github.com/postcss/postcss-custom-properties
  // https://discourse.roots.io/t/how-to-set-up-sage-with-postcss-custom-properties/17611
  const postcssCustomProperties = require('postcss-custom-properties');

  const cssnanoConfig = {
    preset: ['default', { discardComments: { removeAll: true } }]
  };

  return {
    parser: api.options.ctx.enabled.optimize ? 'postcss-safe-parser' : undefined,
    plugins: {
      autoprefixer: true,
      cssnano: api.options.ctx.enabled.optimize ? cssnanoConfig : false,
      'postcss-custom-properties': {
        preserve: true,// keep CSS vars
      },
    },
  };
};
