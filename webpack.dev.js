const common            = require("./webpack.common");
const { merge }         = require('webpack-merge');
const { commonEntries } = require('./webpack-entry-list.js');

const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const WebpackRTLPlugin     = require("webpack-rtl-plugin");

const devConfig = {
    mode: "development", // production | development
    entry: commonEntries,
    watch: true,
    plugins: [
      new MiniCssExtractPlugin({
        filename: "../css/[name].css",
        minify: false,
      }),
      new WebpackRTLPlugin({
        minify: false,
      }),
    ],

    output: {
      filename: "../js/[name].js",
    },

    devtool: 'source-map'
};

const config = merge( common, devConfig );

module.exports = config;
