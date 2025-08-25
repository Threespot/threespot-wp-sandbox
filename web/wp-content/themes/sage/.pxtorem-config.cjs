// PostCSS pixel to rem plugin options
// https://github.com/cuth/postcss-pxtorem
module.exports = {
  exclude: /node_modules/i,
  mediaQuery: false,
  minPixelValue: 0.01,
  propList: ['*', '--*', '!border*'],
  replace: true,
  rootValue: 16,
  selectorBlackList: [],
  unitPrecision: 5,
};
