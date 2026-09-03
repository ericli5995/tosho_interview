# Vendored front-end libraries (generated)

`vue.global.prod.js` and `jquery.min.js` are **not committed**. They are copied
here from `node_modules/` by `bin/copy-assets.js`, which runs as npm's
`postinstall` hook — so `npm install` (or the Docker build) produces them.

Versions are pinned in `package.json`. No bundler is used; the templates load
these files with plain `<script>` tags.
