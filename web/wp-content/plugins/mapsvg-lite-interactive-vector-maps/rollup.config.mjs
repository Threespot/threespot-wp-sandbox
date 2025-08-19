import commonjs from "@rollup/plugin-commonjs"
import json from "@rollup/plugin-json"
import nodeResolve from "@rollup/plugin-node-resolve"
import replace from "@rollup/plugin-replace"
import terser from "@rollup/plugin-terser"
import typescript from "@rollup/plugin-typescript"
import autoprefixer from "autoprefixer"
import cssnano from "cssnano"
import { dirname } from "path"
import del from "rollup-plugin-delete"
import postcss from "rollup-plugin-postcss"
import rootImport from "rollup-plugin-root-import"
import sourcemaps from "rollup-plugin-sourcemaps2"
import { fileURLToPath } from "url"
import packageJson from "./package.json" assert { type: "json" }

const __dirname = dirname(fileURLToPath(import.meta.url))

export default [
  {
    external: ["formidable", "jQuery", "Handlebars"],
    input: { mapsvg: "js/mapsvg/Core/Mapsvg.ts" },
    output: {
      globals: {
        jQuery: "jQuery",
      },
      name: "mapsvg",
      dir: "./dist",
      format: "esm",
      sourcemap: true,
    },
    plugins: [
      del({
        targets: [
          "dist/*",
          "!dist/form-builder.html",
          "!dist/ClusteringWorker.js",
          "!dist/mapsvg-gutenberg.build.js",
        ],
      }),
      replace({
        preventAssignment: true,
        "process.env.VERSION": packageJson.version,
      }),
      postcss({
        extensions: [".css", ".scss"],
        plugins: [autoprefixer(), cssnano()],
        use: [["sass"]],
        extract: "mapsvg-bundle.css", // Specify the output file
        minimize: true, // Minify the CSS
      }),
      rootImport({
        root: `${__dirname}`,
        useInput: "prepend",
        extensions: [".js", ".ts"],
      }),
      /*
            handlebars({
                handlebars: {
                  // The module ID of the Handlebars runtime, exporting `Handlebars` as `default`.
                  // As a shortcut, you can pass this as the value of `handlebars` above.
                  // See the "Handlebars" section below.
                  id: 'Handlebars', // Default: the path of Handlebars' CJS definition within this module
           
                  // Custom handlebars compiler if the built in version is not proper. If you pass this,
                  // you must also pass `id` (above), to ensure that the compiler and runtime versions match.
                  module: import('Handlebars'),
           
                  // Options to pass to Handlebars' `parse` and `precompile` methods.
                  options: {
                    // Whether to generate sourcemaps for the templates
                    sourceMap: true, // Default: true
                  },
                },
           
                // The ID(s) of modules to import before every template, see the "Helpers" section below.
                // Can be a string too.
                helpers: ['./js/mapsvg/vendor/handlebars/handlebars-helpers.ts'], // Default: none
           
                // Whether to register the defined helpers at template declaration in a way that would allow
                // the initialization call to be elided if the template is never used. Useful in a library
                // context where the templates might all get tree-shaken away, leaving no need for the
                // helpers. Does nothing if helpers is empty.
                helpersPureInitialize: true, // Default: false
           
                // In case you want to compile files with other extensions.
                templateExtension: '.html', // Default: '.hbs'
           
                // A function that can determine whether or not a template is a partial.
                isPartial: (name) => name.startsWith('_'), // Default: as at left
           
                // The absolute paths of the root directory(ies) from which to try to resolve the partials.
                // You must also register these with `rollup-plugin-root-import`.
                partialRoot: partialRoots, // Default: none
           
                // The module ID of jQuery, see the "jQuery" section below.
                jquery: 'jQuery', // Default: none
            }),
            */
      typescript({ tsconfig: "./tsconfig.json" }),
      sourcemaps(),
      commonjs(),
      json(),
      nodeResolve({
        browser: true,
      }),
      process.env.NODE_ENV === "production" && terser(),
    ],
  },
]
