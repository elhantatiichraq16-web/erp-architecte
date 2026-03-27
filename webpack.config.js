const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the
// "encore" command. It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'development');
}

Encore
    // Directory where compiled assets will be stored.
    .setOutputPath('public/build/')

    // Public path used by the web server to access the output path.
    .setPublicPath('/build')

    // Only needed for CDN's or subdirectory deploy: the target path for assets.
    // .setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // Will require an extra script tag for runtime.js
    // But, this improves compilation performance in development.
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())

    // Enables hashed filenames (e.g. app.abc123.css).
    .enableVersioning(Encore.isProduction())

    // Configure Babel.
    // Projects can use the .babelrc file for additional customization.
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })

    // Enables and configure @babel/preset-typescript.
    // .enableTypeScriptLoader()

    // Enables Sass/SCSS support.
    .enableSassLoader()

    // Uncomment if you use PostCSS.
    // .enablePostCssLoader()

    // Uncomment if you want to use React.
    // .enableReactPreset()

;

// Export the final configuration
module.exports = Encore.getWebpackConfig();
