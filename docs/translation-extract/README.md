# English static UI extract

Customer-facing strings that **change when the site language switches** (NL ↔ EN ↔ FR).

## Files

| File | Use |
|------|-----|
| `english-by-page.md` | Page-grouped list → paste into Word/Google Docs → export PDF for the client |
| `english-by-page.csv` | Open in Excel; columns include Dutch key + English |
| `summary.json` | Counts per section |

## Regenerate

```bash
node scripts/extract-en-static.mjs
```

## Not included

- Admin panel copy
- CMS/database bodies (home hero, story page, legal HTML, journal articles, product descriptions, FAQs)
- User-generated content

Rows marked _missing from en.json_ are used in the UI but have no English entry yet (EN locale falls back to the Dutch key).
