const TerserPlugin = require('terser-webpack-plugin');
// const { default: ImageminPlugin } = require('imagemin-webpack-plugin');
// const imageminMozjpeg = require('imagemin-mozjpeg');
// const config = require('./config');

module.exports = {
  plugins: [
    // NOTE: Images should be optimized manually to avoid lengthy deploys
    //------------------------------------------------------------------------
    // new ImageminPlugin({
    //   optipng: { optimizationLevel: 2 },
    //   gifsicle: { optimizationLevel: 3 },
    //   pngquant: { quality: '65-90', speed: 4 },
    //   svgo: {
    //     plugins: [
    //       { removeUnknownsAndDefaults: false },
    //       { cleanupIDs: false },
    //       { removeViewBox: false },
    //     ],
    //   },
    //   plugins: [imageminMozjpeg({ quality: 75 })],
    //   disable: (config.enabled.watcher),
    // }),
  ],
  optimization: {
    minimize: true,
    minimizer: [
      // Options https://github.com/terser/terser#minify-options
      new TerserPlugin({
        terserOptions: {
          ecma: 2015
        }
      }),
    ],
  },
};
