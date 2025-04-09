import webpack from "webpack";
import path from "path";
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import CssMinimizerPlugin from "css-minimizer-webpack-plugin";
import TerserPlugin from "terser-webpack-plugin";

const isProduction = process.env.NODE_ENV === "production";

export default {
    mode: isProduction ? "production" : "development",
    devtool: isProduction ? false : "source-map",
    entry: {
        "main": "./src/main.js",
        "admin/main": "./src/admin/main.js",
    },
    externals: {
        jquery: "jQuery",
    },
    output: {
        filename: `[name].min.js`,
        path: path.resolve("dist"),
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
        concatenateModules: true, // Scope hoisting
        minimizer: [
            new TerserPlugin({
                parallel: true,
                extractComments: false,
                terserOptions: {
                    compress: {
                        drop_console: isProduction,
                        drop_debugger: isProduction,
                        passes: 3,
                        ecma: 2015,
                    },
                    format: {
                        comments: false,
                    },
                },
            }),
            new CssMinimizerPlugin(),
        ],
        splitChunks: {
            chunks: "all",
            cacheGroups: {
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
            filename: (pathData) => {
                // Ensure admin CSS is placed in `/dist/admin/`
                return pathData.chunk.name.includes("admin/")
                    ? `[name].min.css`
                    : `[name].min.css`;
            },
        }),
    ],
};