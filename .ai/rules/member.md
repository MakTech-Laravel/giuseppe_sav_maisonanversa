---
paths:
  - resources/js/components/member/**
  - resources/js/layouts/member-layout.tsx
---

# Member

## Member nav mobile drawer
On mobile, MemberNav uses a Menu button plus a left Sheet drawer. Keep the sticky vertical sidebar for md+. Do not restore the horizontal overflow-x-auto strip.

## Member topbar is one row
MemberTopbar is a single row on every breakpoint: logo left, utilities right. On mobile the right side is the notification bell only. Hide the profile avatar until md.

## Language switcher is in the mobile menu
On mobile, hide LanguageSwitcher in MemberTopbar and show it inside the MemberNav sheet. From md up, keep it in the topbar only.
