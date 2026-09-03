#!/usr/bin/env node
/*
 * Copies the prebuilt dist files of the JS dependencies from node_modules/ into
 * public/assets/js/vendor/, where the templates load them with plain <script>
 * tags. Deliberately no bundler: the app uses Vue's global build and jQuery as-is.
 *
 * Runs automatically as npm's postinstall hook; can also be run directly:
 *   node bin/copy-assets.js
 */
"use strict";

const fs = require("fs");
const path = require("path");

const root = path.resolve(__dirname, "..");
const dest = path.join(root, "public", "assets", "js", "vendor");

const files = [
    ["vue/dist/vue.global.prod.js", "vue.global.prod.js"],
    ["jquery/dist/jquery.min.js", "jquery.min.js"],
];

fs.mkdirSync(dest, { recursive: true });

for (const [source, name] of files) {
    const from = path.join(root, "node_modules", source);
    if (!fs.existsSync(from)) {
        console.error(`copy-assets: missing ${from} - run \`npm install\` first`);
        process.exit(1);
    }
    fs.copyFileSync(from, path.join(dest, name));
    console.log(`copy-assets: ${source} -> public/assets/js/vendor/${name}`);
}
