const path = require('path');
const isDev = (process.env.NODE_ENV !== 'production');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const autoprefixer = require('autoprefixer');
const perfectionist = require('postcss-perfectionist');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

module.exports = {
  mode: 'production',
  optimization: {
    minimize: false,  // Disable CSS minification to output readable format
  },
  entry: {
    // ################################################
    // SCSS
    // ################################################
    // Theme
    'theme/varbase_media.common': ['./scss/theme/varbase_media.common.scss'],
    'theme/varbase_media.common_logged': ['./scss/theme/varbase_media.common_logged.scss'],
    'theme/media_library.theme': ['./scss/theme/media_library.theme.scss'],
    'theme/varbase-video-player.theme': ['./scss/theme/varbase-video-player.theme.scss'],
    'theme/varbase-video-player.ckeditor.admin': ['./scss/theme/varbase-video-player.ckeditor.admin.scss'],
    'theme/ai-image-alt-text.inline-button.admin': ['./scss/theme/ai-image-alt-text.inline-button.admin.scss']
  },
  output: {
    path: path.resolve(__dirname, 'css'),
    pathinfo: false,
    publicPath: '',
  },
  module: {
    rules: [
      {
        test: /\.(png|jpe?g|gif|svg)$/,
        exclude: /sprite\.svg$/,
        type: 'javascript/auto',
        use: [{
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
              outputPath: '../../images',
              publicPath: '../../images',
              esModule: false,  // Fix [object Module] issue by disabling ES6 modules
            },
          },
          {
            loader: 'img-loader',
            options: {
              enabled: !isDev,
            },
          },
        ],
      },
      {
        test: /\.(css|scss)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
          },
          {
            loader: 'css-loader',
            options: {
              sourceMap: isDev,
              importLoaders: 2,
              url: false,  // Disable URL processing to keep paths as-is from SCSS
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: isDev,
              postcssOptions: {
                plugins: [
                  autoprefixer(),
                  perfectionist({
                    format: 'expanded',
                    indentSize: 2,
                    trimLeadingZero: true,
                    trimTrailingZeros: true,
                    maxAtRuleLength: false,
                    maxSelectorLength: false,
                    maxValueLength: false,
                  }),
                ],
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: isDev,
              sassOptions: {
                outputStyle: 'expanded',  // Output readable CSS format
                quietDeps: true,  // Suppress deprecation warnings from dependencies
                silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'slash-div'],  // Silence specific deprecations
              },
              // Global SCSS imports:
              additionalData: `
                @use "sass:color";
                @use "sass:math";
              `,
            },
          },
        ],
      },
    ],
  },
  resolve: {
    modules: [
      path.join(__dirname, 'node_modules'),
    ],
    extensions: ['.js', '.json'],
  },
  plugins: [
    new RemoveEmptyScriptsPlugin(),
    new CleanWebpackPlugin({
      cleanStaleWebpackAssets: false
    }),
    new MiniCssExtractPlugin(),
  ],
  watchOptions: {
    aggregateTimeout: 300,
    ignored: ['**/*.woff', '**/*.json', '**/*.woff2', '**/*.jpg', '**/*.png', '**/*.svg', 'node_modules', 'images'],
  }
};
