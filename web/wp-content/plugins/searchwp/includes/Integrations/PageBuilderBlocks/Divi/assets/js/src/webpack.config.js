const defaultConfig = require('@wordpress/scripts/config/webpack.config');

// Create a modified version of the configuration 
const config = { ...defaultConfig };

// Make sure externals exists
if (!config.externals) {
  config.externals = {};
}

// If externals is an object
if (typeof config.externals === 'object' && !Array.isArray(config.externals)) {
  // Remove React from externals
  delete config.externals.react;
  delete config.externals['react-dom'];
}

module.exports = config; 