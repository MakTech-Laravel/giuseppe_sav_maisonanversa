---
paths:
  - resources/js/components/member/**
  - resources/js/layouts/member-layout.tsx
---

# Member

## Member nav mobile drawer
On mobile, MemberNav uses a Menu button plus a left Sheet drawer. Keep the sticky vertical sidebar for md+. Do not restore the horizontal overflow-x-auto strip.

## Member topbar is one row
MemberTopbar is a single row on every breakpoint: logo left, utilities right. On mobile the right side is the notification bell only. Hide the profile avatar until md. The mobile language and menu bar is a sibling under that row, inside the same sticky header, not in the page content.

## Language switcher sits left of the mobile menu
On mobile, render MemberNav with placement="bar" directly under the topbar: LanguageSwitcher on the left, a three-bar menu icon on the right. Keep the word Menu as the button aria-label and the sheet title. Do not put the switcher inside the sheet or in the padded content column. From md up, hide that bar and keep the switcher in the topbar only. The desktop sidebar uses placement="sidebar".
