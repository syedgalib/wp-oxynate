const common = require("./webpack.common");
const { merge } = require('webpack-merge');

const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const WebpackRTLPlugin     = require("webpack-rtl-plugin");
const { vueEntries }       = require('./webpack-entry-list.js');
const VueLoaderPlugin      = require('vue-loader/lib/plugin');

const devConfig = {
    mode: "development", // production | development
    watch: true,
    entry: vueEntries,
    resolve: {
      extensions: [ '.js', '.vue' ],
      alias: {
        'vue$': 'vue/dist/vue.esm.js'
      }
    },

    plugins: [
      new MiniCssExtractPlugin({
        filename: "../css/[name].css",
        minify: false,
      }),
      new WebpackRTLPlugin({
        minify: false,
      }),
      new VueLoaderPlugin(),
    ],

    output: {
      filename: "../js/[name].js",
    },

    devtool: 'source-map'
};

const config = merge( common, devConfig );

module.exports = config;
