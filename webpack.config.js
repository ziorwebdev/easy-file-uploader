const webpack = require("webpack");
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const TerserPlugin = require("terser-webpack-plugin");

const isProduction = process.env.NODE_ENV === "production";

module.exports = {
    mode: isProduction ? "production" : "development",
    devtool: isProduction ? false : "source-map",
    entry: {
        "main": "./src/main.js",
    },
    externals: {
        jquery: "jQuery",
    },
    output: {
        filename: `[name].min.js`,
        path: path.resolve(__dirname, "dist"),
        clean: true,
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, "css-loader"],
            },
        ],
    },
    optimization: {
        usedExports: true, // Enables tree shaking
        minimizer: [
            new TerserPlugin({
                parallel: true,
                extractComments: false,
                terserOptions: {
                    compress: {
                        drop_console: isProduction, // Removes console logs only in production
                        drop_debugger: isProduction,
                        passes: 2,
                    },
                    output: {
                        comments: false,
                    },
                },
            }),
            new CssMinimizerPlugin({
                minimizerOptions: {
                    preset: [
                        "default",
                        {
                            discardComments: { removeAll: true }, // Removes all comments from CSS
                        },
                    ],
                },
            }),
        ],
        splitChunks: {
            chunks: "all",
            cacheGroups: {
                filepond: {
                    test: /[\\/]node_modules[\\/](filepond|filepond-plugin.*)[\\/]/,
                    name: "filepond",
                    chunks: "all",
                    enforce: true,
                },
                common: {
                    test: /[\\/]node_modules[\\/]/,
                    name: "vendors",
                    chunks: "all",
                    enforce: true,
                    priority: -10,
                },
            },
        },
    },
    performance: {
        hints: "warning",
        maxEntrypointSize: 512000,
        maxAssetSize: 1024000,
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: "jquery",
        }),
        new MiniCssExtractPlugin({
            filename: `[name].min.css`,
        }),
    ],
};
