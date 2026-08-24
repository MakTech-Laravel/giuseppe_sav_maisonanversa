---
paths:
  - 'resources/js/pages/admin/letter/**/*.tsx'
---

# Letter

## Heritage Letter is a subscriber index
Admin Heritage Letter is the newsletter subscriber index, not editorial issues. Paginate all subscribers (no 200 cap) with search, status, source, subscriber_locale, and per_page. CSV export uses the same filters. DataPagination must receive meta={paginated}. Skip Brevo UI until credentials exist.
