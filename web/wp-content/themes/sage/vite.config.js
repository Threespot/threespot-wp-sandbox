import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import { createSvgIconsPlugin } from 'vite-plugin-svg-icons'

import fs from 'fs';
import { resolve } from 'path';

import eslint from 'vite-plugin-eslint';
import stylelint from 'vite-plugin-stylelint';

export default defineConfig({
  base: '/wp-content/themes/sage/public/build/',
  envDir: resolve(__dirname, '../../../../'),
  plugins: [
    eslint({
      lintOnStart: true,
      cache: false,
      include: ['resources/**/*.{js,jsx}'],
      exclude: ['node_modules/**', 'public/**'],
    }),
    stylelint({
      include: ['resources/**/*.{css,scss}'],
    }),
    laravel({
      input: [
        'resources/scripts/gutenberg.js',
        'resources/scripts/main.js',
        'resources/styles/admin.scss',
        'resources/styles/critical.scss',
        'resources/styles/gutenberg.scss',
        'resources/styles/login.scss',
        'resources/styles/main.scss',
      ],
      refresh: [
        'resources/views/**/*.blade.php',// only reload Blade template updates
      ],
      url: process.env.APP_URL,
    }),
    viteStaticCopy({
      targets: [
        {
          src: 'resources/images',
          dest: 'assets'
        },
        {
          src: 'resources/fonts',
          dest: 'assets'
        },
        {
          src: 'resources/scripts/lib/jquery-3.7.1.min.js',
          dest: ''// copy to public folder root
        }
      ]
    }),
    // SVG sprite plugin
    // Requires adding `import 'virtual:svg-icons-register';` in main.js
    // https://github.com/vbenjs/vite-plugin-svg-icons
    createSvgIconsPlugin({
      // customDomId: '__svg__icons__dom__',
      iconDirs: [resolve(process.cwd(), 'resources/images/sprite')],
      inject: 'body-first',// 'body-last'
      symbolId: 'sprite-[name]',// 'icon-[dir]-[name]'
      // Custom SVGO config
      // https://svgo.dev/docs/preset-default/#plugins-list
      svgoOptions: {
        plugins: [
          {name: 'cleanupIDs', active: false},
          {name: 'collapseGroups', active: false},
          {name: 'removeUselessDefs', active: false},
          {name: 'removeUselessStrokeAndFill', active: false},
          {name: 'convertShapeToPath', active: false},
        ],
      },
    }),
    wordpressPlugin(),
    // Generate the theme.json file in the public/build/assets directory
    // based on the Tailwind config and the theme.json file from base theme folder
    wordpressThemeJson({
      disableTailwindColors: true,
      disableTailwindFonts: true,
      disableTailwindFontSizes: true,
    }),
  ],
  // Configure esbuild to transform JSX using the Gutenberg runtime (wp.element)
  // so we can write JSX in .js files without importing React.
  esbuild: {
    jsxFactory: 'wp.element.createElement',
    jsxFragment: 'wp.element.Fragment',
    // Optionally, inject wp import into modules that use JSX (not required if wp is global)
    // jsxInject: "import * as wp from 'wp';",
  },
  resolve: {
    alias: {
      '@scripts': '/resources/scripts',
      '@styles': '/resources/styles',
      '@fonts': '/resources/fonts',
      '@images': '/resources/images',
      '@vars': '/resources/styles/vars',
      '@functions': '/resources/styles/functions',
      '@mixins': '/resources/styles/mixins',
    },
    // Allow importing .jsx if needed
    extensions: ['.mjs', '.js', '.ts', '.jsx', '.json'],
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    // See readme.md in /certs for how to generate the certificates
    https: {
      key: fs.readFileSync(resolve(__dirname, 'certs/threespot-wp-sandbox.lndo.site-key.pem')),
      cert: fs.readFileSync(resolve(__dirname, 'certs/threespot-wp-sandbox.lndo.site.pem')),
    },
    hmr: {
      host: 'wide-angle.lndo.site',
      protocol: 'wss',
    },
  },
})
