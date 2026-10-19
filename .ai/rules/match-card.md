---
paths:
  - 'resources/match-card/**'
---

# Match Card

## Match cards render with Satori + resvg via node, cached by content hash
MatchCardRenderer runs `node resources/match-card/render.mjs <out.png>` with the payload JSON on stdin (UTF-8; PowerShell pipes mangle accents, pass a file path as 2nd arg when testing by hand). Satori: content-box sizing, every multi-child div needs display:flex, fonts must be TTF. PNGs cache on the local disk at match-cards/{id}/{format}-{locale}-{hash}.png; bump maison.match_cards.template_version after changing the layout so old PNGs regenerate. Production image must keep node + node_modules (satori, @resvg/resvg-js).

## Production Docker needs fontconfig, resvg smoke check, writable private storage
Dockerfile installs fontconfig + fonts-dejavu-core and runs `node -e "import('@resvg/resvg-js')"` after npm ci so a missing native binary fails the image build. start.sh mkdir/chowns storage/app/private after Coolify volume mounts. MatchCardRenderer sets Process::path(base_path()). MATCH_CARD_NODE defaults to node. Share-page roster must call players($session, $locale) so __('Privé') follows the URL.

## Production match-card needs fontconfig, Node smoke check, writable private storage
Dockerfile installs fontconfig + fonts-dejavu-core and runs `node -e "import('@resvg/resvg-js')"` after npm ci so a missing native binary fails the image build. start.sh mkdir/chowns storage/app/private after Coolify volume mounts. MatchCardRenderer sets Process::path(base_path()). Keep node_modules (satori, @resvg); MATCH_CARD_NODE defaults to node. Share-page roster must call players($session, $locale) so __('Privé') follows the URL.
