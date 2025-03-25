const isDev = process.env.NODE_ENV !== 'production';

module.exports = {
  plugins: [
    // Plugins for PostCSS
    require('autoprefixer'), // Parses CSS and adds vendor prefixes.
    require('postcss-preset-env'), // Convert modern CSS into something most browsers can understand.
    require('postcss-nested'), // Unwrap nested rules like how Sass does it.
    require('postcss-nesting'), // Nest style rules inside each other, following the CSS Nesting specification.
    require('./postcss.plugins'), // Strip inline comments.
  ],
};
