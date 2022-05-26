const mix = require("laravel-mix");

if (mix == 'undefined') {
    const { mix } = require("laravel-mix");
}

require("laravel-mix-merge-manifest");

if (mix.inProduction()) {
    var publicPath = 'publishable/assets';
} else {
    var publicPath = "../../../public/ui/assets";
}

mix.setPublicPath(publicPath).mergeManifest();
mix.disableNotifications();

mix.inProduction()

mix.js(
    [
        __dirname + "/src/Resources/assets/js/app.js",
    ],
    "js/ui.js"
).vue({ version: 2 })
    .copy(__dirname + "/src/Resources/assets/images", publicPath + "/images")
    .postCss(__dirname + "/src/Resources/assets/css/app.css", "css/ui.css", [
        require("tailwindcss"),
    ])
    .options({
        processCssUrls: false
    });

if (!mix.inProduction()) {
    mix.sourceMaps();
}

if (mix.inProduction()) {
    mix.version();
}
