#!/usr/bin/env bash
# Re-download the pinned front-end libraries and verify their SHA-256 hashes.
# No npm - this is the whole "package manager" for JS in this project.
set -euo pipefail

cd "$(dirname "$0")/.."
DEST="public/assets/js/vendor"

# name|version|url|sha256
LIBS=(
  "vue.global.prod.js|3.4.38|https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.38/vue.global.prod.js|b50eeefe35d41636bb96c92b40f1df0b4fb7914e07b3c625b1ec15e9748767b9"
  "jquery.min.js|3.7.1|https://code.jquery.com/jquery-3.7.1.min.js|fc9a93dd241f6b045cbff0481cf4e1901becd0e12fb45166a8f17f95823f0b1a"
)

if command -v sha256sum >/dev/null 2>&1; then
  SHA="sha256sum"
else
  SHA="shasum -a 256"
fi

for entry in "${LIBS[@]}"; do
  IFS='|' read -r name version url want <<<"$entry"
  echo "-> ${name} (${version})"
  curl -sSfL -o "${DEST}/${name}" "$url"
  got="$(${SHA} "${DEST}/${name}" | awk '{print $1}')"
  if [ "$got" != "$want" ]; then
    echo "   SHA-256 MISMATCH for ${name}" >&2
    echo "   expected ${want}" >&2
    echo "   got      ${got}" >&2
    exit 1
  fi
  echo "   ok ${got}"
done

echo "All vendor files verified."
