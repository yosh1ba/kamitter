const VueLoaderPlugin = require("vue-loader/lib/plugin");
const path = require('path');

module.exports = {
  mode: "production",
  entry: "./resources/js/app.js",
  output: {
    // 出力ファイル名
    filename: 'app.js',
    //  出力ファイルのディレクトリ名
    path: path.resolve(__dirname, 'public/js')

  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: "vue-loader",
      },
      {
        test: /\.js$/,
        loader: "babel-loader",
      },
      {
        test: /\.css$/,
        use: [
          "vue-style-loader",
          "css-loader",
        ],
      },
    ]
  },
  plugins: [
    new VueLoaderPlugin()
  ],
  resolve: {
    extensions: [".vue", ".js"],
    // alias: {
    //   "vue$": "vue/dist/vue.esm.js"
    // }
  },
  performance: { hints: false } // これ
}