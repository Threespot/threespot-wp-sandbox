# Threespot WordPress Sandbox

[![CircleCI](https://circleci.com/gh/Threespot/threespot-wp-sandbox.svg?style=shield)](https://circleci.com/gh/Threespot/threespot-wp-sandbox)
[![Dashboard threespot-wp-sandbox](https://img.shields.io/badge/dashboard-threespot_wp_sandbox-yellow.svg)](https://dashboard.pantheon.io/sites/08dda8d9-dc1e-4281-8dfa-8091d868d503#dev/code)
[![Dev Site threespot-wp-sandbox](https://img.shields.io/badge/site-threespot_wp_sandbox-blue.svg)](http://dev-threespot-wp-sandbox.pantheonsite.io/)
[![WordPress Core](https://img.shields.io/badge/WordPress-5.3.1-blue.svg)](https://wordpress.org/download/)

# About

This is a repository to try out custom plugins, custom Gutenberg blocks, etc. Get wild, but not too wild.

# Child Theme

The `twentytwenty-child` directory is a child theme based off the WP Twentytwenty. Use this to test simple custimizations.

# Hosting

This site is hosted on Pantheon on the Basic plan ([details](https://pantheon.io/plans/basic-pricing)). It's running on WordPress 5 on PHP 7.1. Redis cache is not available to sites on the Basic plan.

# Local Development

In order to more easily recreate the production environment locally, [Lando](https://docs.devwithlando.io) is used for local development. We also use Pantheon’s CLI, Terminus, to sync files and databases.

### Prerequisites

1. Install git
   - [Windows installer](https://gitforwindows.org)
   - macOS
     - Check if already installed with `$ git --version`
     - If not already installed, you will be prompted to install it.
1. Install Lando ([Windows](https://docs.devwithlando.io/installation/windows.html), [macOS](https://docs.devwithlando.io/installation/macos.html))
1. Install [Terminus](https://pantheon.io/docs/terminus/install/), Pantheon’s CLI tool
1. Install Composer
   - [Windows instructions](https://getcomposer.org/doc/00-intro.md#installation-windows)
   - macOS
     - Install [Homebrew](https://brew.sh)
     - Run `$ brew install composer`
1. Install NodeJS 8.x ([Windows](https://nodejs.org/dist/latest-v8.x/node-v8.16.0-x64.msi), [macOS](https://nodejs.org/dist/latest-v8.x/node-v8.16.0.pkg))
   - Note: We recommend [asdf](https://github.com/asdf-vm/asdf) for managing multiple versions of Node
1. Install Yarn ([Windows](https://yarnpkg.com/en/docs/install#windows-stable), [macOS](https://yarnpkg.com/en/docs/install#mac-stable))

   - Note: Yarn is a faster alternative to `npm install`

   ### Add ENV File

Create a `.env` file.
Update it so that it looks something like the following:

```
DB_NAME=wordpress
DB_USER=wordpress
DB_PASSWORD=wordpress

# Optional variables
DB_HOST=database
# DB_PREFIX=wp_

WP_ENV=development
WP_HOME=http://APP_NAME.lndo.site
WP_SITEURL=${WP_HOME}/wp

# Generate your keys here: https://roots.io/salts.html
AUTH_KEY='generateme'
SECURE_AUTH_KEY='generateme'
LOGGED_IN_KEY='generateme'
NONCE_KEY='generateme'
AUTH_SALT='generateme'
SECURE_AUTH_SALT='generateme'
LOGGED_IN_SALT='generateme'
NONCE_SALT='generateme'

#plugin keys
ACF_PRO_KEY=`pluginkey`


```

### Starting local server

1. Clone this repo

   `$ git clone git@github.com:Threespot/threespot-wp-sandbox.git`

1. Open `threespot-wp-sandbox` folder in the terminal and install Composer dependencies

   `$ composer install`

1. Start the local server

   `$ lando start`

   1. If everything was successful, you should see something like this:

      ```
      Your app has started up correctly.
      Here are some vitals:

       NAME                  threespot-wp-sandbox
       LOCATION              /Users/yourUserName/Sites/threespot-wp-sandbox
       SERVICES              appserver_nginx, appserver, database, cache, edge, edge_ssl, index
       APPSERVER_NGINX URLS  https://localhost:32774
                             http://localhost:32775
       EDGE URLS             http://localhost:32776
                             https://threespot-wp-sandbox.lndo.site/:8000
                             https://threespot-wp-sandbox.lndo.site/
       EDGE_SSL URLS         https://localhost:32777
      ```

1. Clone the database and files from Pantheon

   `$ lando pull`

   1. Use the arrow keys to select “none” for the code, since we pulled the code from GitHub:
      ```
      ? Pull code from?
        live
        dev
        test
      ❯ none
      ```
   1. Select “live” for the database:
      ```
      ? Pull database from?
      ❯ live
        dev
        test
        none
      ```
   1. Select “live” for the files (NOTE: ALDF has ~4,000 images, so if this takes too long you can select “none” to skip this but you will not see any images locally)
      ```
      ? Pull files from?
      ❯ live
        dev
        test
        none
      ```

1. You should now be able to view the site at https://threespot-wp-sandbox.lndo.site/

   1. Your browser may warn you that the site is not secure, this is expected. Click though the warning message to proceed.

      ![browser-warning](https://user-images.githubusercontent.com/864716/56390671-8c97fd00-61fa-11e9-8127-39c190486f5b.png)

1. You should be able to login to the admin at https://threespot-wp-sandbox.lndo.site/wp/wp-admin/

#### Troubleshooting

If the site fails to load, try the following:

- Restart the server

  `$ lando restart`

- Rebuild the app

  `$ lando rebuild`

- Destroy and recreate the app

  `$ lando destroy && lando start`

- Check current version of Lando to see if there’s an [update available](https://github.com/lando/lando/releases)

  `$ lando version`

### Starting Webpack with live reload

If you plan to make CSS or JS updates, you must run Webpack to recompile and inject the CSS and JS.

1. In a new terminal window, navigate to the `threespot-wp-sandbox` project folder again
1. Navigate to the theme folder

   `$ cd /web/wp-content/themes/threespot-wp-sandbox`

1. Install npm dependencies using Yarn

   `$ yarn install`

1. Start Webpack

   `$ yarn start`

   1. You should see the following:
      ```
      [HTML Injector] Running...
      [Browsersync] Proxying: https://threespot-wp-sandbox.lndo.site
      [Browsersync] Access URLs:
       -----------------------------------
             Local: https://localhost:3000
          External: https://10.0.5.35:3000
       -----------------------------------
                UI: http://localhost:3001
       UI External: http://localhost:3001
       -----------------------------------
      [Browsersync] Watching files...
      ```

1. You should now be able to view the site locally at https://localhost:3000
1. To stop the server, press <kbd>Control</kbd> + <kbd>C</kbd>

#### Troubleshooting

1. If the server won’t start or gets caught in an endless loop, try stopping and restarting it.
1. If the server starts but the site doesn’t appear at https://localhost:3000, check that `devUrl` in [`config.json`](https://github.com/Threespot/threespot-wp-sandbox/blob/76b6d3addc5802ea9ef61f5901128a4ea2d318e0/web/wp-content/themes/threespot-wp-sandbox/resources/assets/config.json#L8) matches the URL generated by Lando (e.g. https://threespot-wp-sandbox.lndo.site)

## Updating Dependencies

To update WordPress or any free plugins to the latest version, run `$ composer update` and push the updated `composer.lock` file to GitHub. To update any paid plugins, you will need to manually update the files and commit.

To update the Node dependencies, run `$ yarn upgrade-interactive` from the theme folder (`threespot-wp-sandbox/web/wp-content/themes/threespot-wp-sandbox`) and select the packages to update. Once installed, push the updated `yarn.lock` file to GitHub.

It is technically possible to install plugins via the WordPress admin with Pantheon ([docs](https://pantheon.io/docs/guides/wordpress-git/plugins/)) but this isn’t recommended since it makes it difficult for multiple developers to stay in sync.

## Deployment

Every time code is pushed to GitHub, [CircleCI](https://circleci.com/gh/Threespot/threespot-wp-sandbox) will build and deploy the files to Pantheon’s [dev environment](https://dashboard.pantheon.io/sites/c0626448-2830-4bf7-852c-575f3150a227#dev/code). You can tell CircleCI not run by adding `[skip ci]` to the commit message.

Once code has been deployed to the dev environment, you can switch to the “Test” tab and click the “Deploy Code from Development to Test Environment” button to deploy to http://test-threespot-wp-sandbox.pantheonsite.io.

![pantheon-deploy](https://user-images.githubusercontent.com/864716/56390569-55295080-61fa-11e9-9c35-94d5a8ee500d.png)

After the updates have been tested, you can deploy to the live site by following the same process on the “Live” tab.

# Tech Stack

### Back End

- [Bedrock](https://roots.io/bedrock/) framework
  - Places core WordPress code in a separate folder (required for Pantheon)
  - Composer integration
  - Environment variable support
  - Supports autoloading regular and must-use plugins
- [Sage](https://roots.io/sage/) theme framework ([docs](https://roots.io/sage/docs/theme-configuration-and-setup/)) with [Soil](https://roots.io/plugins/soil/) plugin
  - Supports [Laravel’s Blade](https://laravel.com/docs/5.5/blade) templating engine
  - Supports controllers ([docs](https://github.com/soberwp/controller)) to keep views DRY
  - Includes [Webpack](https://webpack.js.org) for processing front-end assets ([config](https://github.com/Threespot/aldf-wp/blob/master/web/wp-content/themes/aldf/resources/assets/build/webpack.config.js))
    - Sass linting, compilation, browser prefixing, and minification
    - JavaScript linting, transpilation (i.e. converting ES6 to ES5), bundling, and minification
    - Image compression ([config](https://github.com/Threespot/aldf-wp/blob/master/web/wp-content/themes/aldf/resources/assets/build/webpack.config.optimize.js))
    - Live reload with [BrowserSync](https://www.browsersync.io)
- Community support for both frameworks can be found at https://discourse.roots.io

#### CSS

- Threespot’s [CSS reset](https://github.com/Threespot/frontline-css-reset)
- Threespot’s [Sass library](https://github.com/Threespot/frontline-sass) ([docs](https://threespot.github.io/frontline-sass/documentation/))
  - Most often used functions and mixins:
    - [`fs-rem`](https://threespot.github.io/frontline-sass/documentation/#main-function-fs-rem)—Helper function converts pixels to rems
    - [`fs-color`](https://threespot.github.io/frontline-sass/documentation/#main-function-fs-color)—Helper for retrieving a color from the [`$fs-colors`](https://threespot.github.io/frontline-sass/documentation/#main-variable-fs-colors) map
    - [`fs-min-width`](https://threespot.github.io/frontline-sass/documentation/#main-mixin-fs-min-width)/[`fs-max-width`](https://threespot.github.io/frontline-sass/documentation/#main-mixin-fs-max-width)—Media query helpers to convert fixed pixels to rems
    - [`fs-media`](https://threespot.github.io/frontline-sass/documentation/#main-mixin-fs-media)—Media query helper that supports any condition, and multiple conditions
    - [`fs-scale`](https://threespot.github.io/frontline-sass/documentation/#main-mixin-fs-scale)—Allows for smoothly scaling values from one viewport width to another
    - [`fs-attention`](https://threespot.github.io/frontline-sass/documentation/#selectors-mixin-fs-attention)—Helper mixin adds `:hover`, `:focus`, and `:active` states
    - [`fs-svg`](https://threespot.github.io/frontline-sass/documentation/#main-function-fs-svg)—Function to export URL-escaped inline SVG for use in `background-image` (SVGs are stored in [`$fs-svg-icons`](https://threespot.github.io/frontline-sass/documentation/#main-variable-fs-svg-icons) map)
- [SUIT](http://suitcss.github.io/) CSS class naming convention, e.g. `.ComponentName-child--variation`

#### JavaScript

- [Modernizr](https://modernizr.com/download/?-cssgrid_cssgridlegacy-objectfit-setclasses) used for [CSS grid](https://caniuse.com/#feat=css-grid) and [object-fit](https://caniuse.com/#feat=object-fit&search=object-fit) feature detection
- External JS dependencies are listed in the `dependencies` section of [`package.json`](https://github.com/Threespot/aldf-wp/blob/master/web/wp-content/themes/aldf/package.json#L68):

  <details><summary>View dependencies</summary>

  - [@threespot/expand-toggle](https://www.npmjs.com/package/@threespot/expand-toggle)
  - [@threespot/fluid-iframe](https://www.npmjs.com/package/@threespot/fluid-iframe)
  - [@threespot/fluid-svg-polyfill](https://www.npmjs.com/package/@threespot/fluid-svg-polyfill)
  - [@threespot/freeze-scroll](https://www.npmjs.com/package/@threespot/freeze-scroll)
  - [@threespot/mailto](https://www.npmjs.com/package/@threespot/mailto)
  - [@threespot/object-fit-image](https://www.npmjs.com/package/@threespot/object-fit-image)
  - [@threespot/unorphanize](https://www.npmjs.com/package/@threespot/unorphanize)
  - [console-polyfill](https://www.npmjs.com/package/console-polyfill)
  - [flickity-fullscreen](https://www.npmjs.com/package/flickity-fullscreen)
  - [fontfaceobserver](https://www.npmjs.com/package/fontfaceobserver)
  - [lodash](https://www.npmjs.com/package/lodash)
  - [mdn-polyfills](https://www.npmjs.com/package/mdn-polyfills)
  - [picturefill ](https://www.npmjs.com/package/picturefill)
  - [what-input](https://www.npmjs.com/package/what-input)

    </details>

    - Custom JS modules can be found in the [`/resources/assets/scripts`](https://github.com/Threespot/threespot-wp-sandbox/tree/master/web/wp-content/themes/threespot-wp-sandbox/resources/assets/scripts) folder

- The site JS is split into three files:

  - [`critical.js`](https://github.com/Threespot/threespot-wp-sandbox/blob/master/web/wp-content/themes/threespot-wp-sandbox/resources/assets/scripts/critical.js)—Contains code that needs to run before content is displayed in the browser to prevent a [FOUC](https://en.wikipedia.org/wiki/Flash_of_unstyled_content) (e.g. check is JS is enabled, check if fonts have previously loaded)
    - NOTE: This code must be manually minified and copied to [`critical-js.blade.php`](https://github.com/Threespot/threespot-wp-sandbox/blob/master/web/wp-content/themes/threespot-wp-sandbox/resources/views/partials/critical-js.blade.php) (easier than setting up a custom build script since this code won’t change often)
  - [`main.js`](https://github.com/Threespot/threespot-wp-sandbox/blob/master/web/wp-content/themes/threespot-wp-sandbox/resources/assets/scripts/main.js)—Contains all other site JS, with the exception of image galleries; loaded at the bottom the `<body>` tag
