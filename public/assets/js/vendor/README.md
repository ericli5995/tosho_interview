# Vendored front-end libraries

No npm / no build step. These files are committed as-is and loaded with plain
`<script>` tags. `bin/vendor-sync.sh` re-downloads them and verifies the hashes
below (this file is the lockfile).

| File | Version | Source URL | SHA-256 |
| --- | --- | --- | --- |
| `vue.global.prod.js` | 3.4.38 | https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.38/vue.global.prod.js | `b50eeefe35d41636bb96c92b40f1df0b4fb7914e07b3c625b1ec15e9748767b9` |
| `jquery.min.js` | 3.7.1 | https://code.jquery.com/jquery-3.7.1.min.js | `fc9a93dd241f6b045cbff0481cf4e1901becd0e12fb45166a8f17f95823f0b1a` |

Vue is used for the product-search UI (`/products/search`) and the admin image
uploader. jQuery is used for small public-site behaviour (mobile nav).
