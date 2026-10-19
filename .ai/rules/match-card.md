---
paths:
  - 'resources/match-card/**'
---

# Match Card

## Match cards render with Satori + resvg via node, cached by content hash
MatchCardRenderer runs `node resources/match-card/render.mjs <out.png>` with the payload JSON on stdin (UTF-8; PowerShell pipes mangle accents, pass a file path as 2nd arg when testing by hand). Satori: content-box sizing, every multi-child div needs display:flex, fonts must be TTF. PNGs cache on the local disk at match-cards/{id}/{format}-{locale}-{hash}.png; bump maison.match_cards.template_version after changing the layout so old PNGs regenerate. Production image must keep node + node_modules (satori, @resvg/resvg-js).
