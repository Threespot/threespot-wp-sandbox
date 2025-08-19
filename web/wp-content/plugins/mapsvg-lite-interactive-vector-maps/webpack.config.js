import { dirname } from "path"
import { fileURLToPath } from "url"

const __dirname = dirname(fileURLToPath(import.meta.url))

export default {
  entry: "./js/mapsvg-admin/gutenberg/mapsvg-gutenberg.jsx",
  output: {
    path: __dirname,
    filename: "dist/mapsvg-gutenberg.build.js",
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: [
          {
            loader: "babel-loader",
            options: {
              presets: ["@babel/preset-react"],
            },
          },
        ],
      },
    ],
  },
}
