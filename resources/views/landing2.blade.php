<!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@if(!empty($noindex))
<meta name="robots" content="noindex, nofollow">
@endif
<title>На Репите — Нейросеть для генерации песен онлайн | ИИ для создания музыки</title>
<meta name="description" content="Создайте свою песню за 2 минуты: нейросеть сгенерирует текст, музыку и живой русский вокал без акцента. Без VPN и регистрации, оплата российскими картами.">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<style>/* ============================================================
   На Репите — дизайн-система главной (dark premium)
   ============================================================ */
/* cyrillic-ext */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/9862c62c-ac82-4770-9e17-193feb9cd11c.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/71964dff-0831-48b2-96bf-9f4139a57f83.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* greek */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/94207d9b-8375-4beb-9dc3-ad73765aa67e.woff2") format('woff2');
  unicode-range: U+0370-0377, U+037A-037F, U+0384-038A, U+038C, U+038E-03A1, U+03A3-03FF;
}
/* vietnamese */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/83a82a85-955b-4027-988f-6b425297fae9.woff2") format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/5b8764cd-c82e-4ca7-a53c-39f93b369db4.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/e52359e7-cf41-46f9-8204-ca18726c537f.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/9862c62c-ac82-4770-9e17-193feb9cd11c.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/71964dff-0831-48b2-96bf-9f4139a57f83.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* greek */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/94207d9b-8375-4beb-9dc3-ad73765aa67e.woff2") format('woff2');
  unicode-range: U+0370-0377, U+037A-037F, U+0384-038A, U+038C, U+038E-03A1, U+03A3-03FF;
}
/* vietnamese */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/83a82a85-955b-4027-988f-6b425297fae9.woff2") format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/5b8764cd-c82e-4ca7-a53c-39f93b369db4.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/e52359e7-cf41-46f9-8204-ca18726c537f.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/9862c62c-ac82-4770-9e17-193feb9cd11c.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/71964dff-0831-48b2-96bf-9f4139a57f83.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* greek */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/94207d9b-8375-4beb-9dc3-ad73765aa67e.woff2") format('woff2');
  unicode-range: U+0370-0377, U+037A-037F, U+0384-038A, U+038C, U+038E-03A1, U+03A3-03FF;
}
/* vietnamese */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/83a82a85-955b-4027-988f-6b425297fae9.woff2") format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/5b8764cd-c82e-4ca7-a53c-39f93b369db4.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'JetBrains Mono';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/e52359e7-cf41-46f9-8204-ca18726c537f.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/baa1fd2d-551f-43d0-a2d8-2c28b5bb9f80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/c3431499-e20c-443f-a4ed-3046da433ea6.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/c7a377cc-53fb-4ac1-9b43-07afeeacc834.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url("/static/landing2/190fd534-5c20-46b8-b6f0-91f85a503dcb.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/baa1fd2d-551f-43d0-a2d8-2c28b5bb9f80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/c3431499-e20c-443f-a4ed-3046da433ea6.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/c7a377cc-53fb-4ac1-9b43-07afeeacc834.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url("/static/landing2/190fd534-5c20-46b8-b6f0-91f85a503dcb.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("/static/landing2/baa1fd2d-551f-43d0-a2d8-2c28b5bb9f80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("/static/landing2/c3431499-e20c-443f-a4ed-3046da433ea6.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("/static/landing2/c7a377cc-53fb-4ac1-9b43-07afeeacc834.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url("/static/landing2/190fd534-5c20-46b8-b6f0-91f85a503dcb.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/baa1fd2d-551f-43d0-a2d8-2c28b5bb9f80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/c3431499-e20c-443f-a4ed-3046da433ea6.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/c7a377cc-53fb-4ac1-9b43-07afeeacc834.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url("/static/landing2/190fd534-5c20-46b8-b6f0-91f85a503dcb.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url("/static/landing2/baa1fd2d-551f-43d0-a2d8-2c28b5bb9f80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url("/static/landing2/c3431499-e20c-443f-a4ed-3046da433ea6.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url("/static/landing2/c7a377cc-53fb-4ac1-9b43-07afeeacc834.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url("/static/landing2/190fd534-5c20-46b8-b6f0-91f85a503dcb.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* cyrillic-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 900;
  font-display: swap;
  src: url("/static/landing2/baa1fd2d-551f-43d0-a2d8-2c28b5bb9f80.woff2") format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 900;
  font-display: swap;
  src: url("/static/landing2/c3431499-e20c-443f-a4ed-3046da433ea6.woff2") format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* latin-ext */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 900;
  font-display: swap;
  src: url("/static/landing2/c7a377cc-53fb-4ac1-9b43-07afeeacc834.woff2") format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Onest';
  font-style: normal;
  font-weight: 900;
  font-display: swap;
  src: url("/static/landing2/190fd534-5c20-46b8-b6f0-91f85a503dcb.woff2") format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}


:root{
  /* surfaces */
  --bg:#0C0A12;
  --bg-2:#0F0B17;
  --surface:#171121;
  --surface-2:#1E1730;
  --surface-hi:#271D3D;
  --border:rgba(255,255,255,.09);
  --border-hi:rgba(255,255,255,.17);

  /* text */
  --text:#F4F1FA;
  --text-mid:#B8B1C8;
  --text-dim:#857E97;

  /* accents */
  --coral:#FF5E7A;
  --violet:#B45CFF;
  --cyan:#4ED9E8;
  --green:#3FD98B;
  --grad:linear-gradient(98deg,#FF5E7A 0%,#C45CFF 100%);
  --grad-soft:linear-gradient(98deg,rgba(255,94,122,.16),rgba(180,92,255,.16));
  --glow-coral:0 14px 44px -12px rgba(255,94,122,.55);
  --glow-violet:0 14px 50px -14px rgba(180,92,255,.5);

  /* layout */
  --maxw:1200px;
  --r-sm:10px;
  --r:16px;
  --r-lg:24px;
  --r-xl:32px;

  /* type */
  --font:'Onest',system-ui,sans-serif;
  --mono:'JetBrains Mono',ui-monospace,monospace;
}

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
  margin:0;background:var(--bg);color:var(--text);
  font-family:var(--font);font-weight:400;
  -webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;
  font-size:17px;line-height:1.6;overflow-x:hidden;
}
img{display:block;max-width:100%}
a{color:inherit;text-decoration:none}
h1,h2,h3,h4{margin:0;letter-spacing:-.02em;line-height:1.05;font-weight:800}
p{margin:0}
::selection{background:rgba(180,92,255,.4);color:#fff}

/* background ambience */
body{
  background:
    radial-gradient(900px 620px at 84% -10%, rgba(180,92,255,.22), transparent 60%),
    radial-gradient(820px 560px at 4% 2%, rgba(255,94,122,.16), transparent 58%),
    radial-gradient(720px 700px at 50% 38%, rgba(78,217,232,.06), transparent 60%),
    var(--bg);
}

/* ---------- layout helpers ---------- */
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 32px}
.section{padding:104px 0;position:relative}
.section--tight{padding:80px 0}
.eyebrow{
  display:inline-flex;align-items:center;gap:9px;
  font-family:var(--mono);font-size:13px;font-weight:500;letter-spacing:.14em;
  text-transform:uppercase;color:var(--violet);margin-bottom:20px;
}
.eyebrow::before{content:"";width:22px;height:2px;border-radius:2px;background:var(--grad)}
.h-sec{font-size:48px;font-weight:800;letter-spacing:-.03em;line-height:1.06;max-width:18ch}
.h-sec .em{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.sub-sec{font-size:19px;color:var(--text-mid);margin-top:18px;max-width:60ch;line-height:1.55}
.sec-head{margin-bottom:52px}
.center{text-align:center;margin-left:auto;margin-right:auto}
.center .sub-sec,.center .h-sec{margin-left:auto;margin-right:auto}

/* ---------- buttons ---------- */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;
  font-family:var(--font);font-weight:700;font-size:17px;letter-spacing:-.01em;
  border:none;cursor:pointer;border-radius:999px;padding:17px 30px;
  transition:transform .18s cubic-bezier(.2,.8,.2,1),box-shadow .22s,background .2s,border-color .2s,color .2s;
  position:relative;white-space:nowrap;
}
.btn svg{width:19px;height:19px;flex:0 0 auto}
.btn--primary{background:var(--grad);color:#fff;box-shadow:var(--glow-coral)}
.btn--primary:hover{transform:translateY(-2px);box-shadow:0 20px 54px -10px rgba(255,94,122,.7)}
.btn--primary:active{transform:translateY(0) scale(.98);box-shadow:0 8px 24px -8px rgba(255,94,122,.6)}
.btn--primary:focus-visible{outline:none;box-shadow:var(--glow-coral),0 0 0 4px rgba(180,92,255,.45)}
.btn--ghost{background:rgba(255,255,255,.04);color:var(--text);border:1.5px solid var(--border-hi)}
.btn--ghost:hover{background:rgba(255,255,255,.09);border-color:rgba(255,255,255,.32);transform:translateY(-2px)}
.btn--ghost:active{transform:translateY(0) scale(.98)}
.btn--ghost:focus-visible{outline:none;box-shadow:0 0 0 4px rgba(180,92,255,.4)}
.btn--lg{padding:20px 38px;font-size:19px}
.btn--sm{padding:12px 20px;font-size:15px}
.btn[disabled],.btn--disabled{opacity:.4;pointer-events:none;box-shadow:none;filter:grayscale(.4)}
.btn--block{width:100%}
.btn-spin{width:18px;height:18px;border-radius:50%;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ---------- header ---------- */
.hdr{position:sticky;top:0;z-index:60;backdrop-filter:blur(18px);
  background:rgba(12,10,18,.72);border-bottom:1px solid var(--border)}
.hdr-in{display:flex;align-items:center;gap:32px;height:74px}
.logo{display:flex;align-items:center;gap:11px;font-weight:800;font-size:20px;letter-spacing:-.02em}
.logo-mark{width:38px;height:38px;border-radius:11px;background:var(--grad);display:grid;place-items:center;box-shadow:var(--glow-coral)}
.logo-mark svg{width:18px;height:18px}
.logo b{font-weight:800}
.logo span{color:var(--violet)}
.nav{display:flex;gap:6px;margin-left:14px}
.nav a{padding:9px 14px;border-radius:10px;color:var(--text-mid);font-size:15.5px;font-weight:500;transition:.16s;white-space:nowrap}
.nav a:hover{color:var(--text);background:rgba(255,255,255,.05)}
.hdr-cta{margin-left:auto;display:flex;align-items:center;gap:14px}
.link-login{font-weight:600;font-size:15.5px;color:var(--text-mid);transition:.16s}
.link-login:hover{color:var(--text)}

/* ---------- hero ---------- */
.hero{position:relative;overflow:hidden;padding:84px 0 96px}
.hero-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:56px;align-items:center}
.hero h1{font-size:62px;font-weight:900;letter-spacing:-.035em;line-height:1.02}
.hero h1 .em{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.hero-sub{font-size:20px;line-height:1.55;color:var(--text-mid);margin-top:24px;max-width:46ch}
.hero-cta{display:flex;gap:14px;margin-top:36px;flex-wrap:wrap}
.trust{display:flex;align-items:center;gap:10px;margin-top:30px;flex-wrap:wrap;
  font-family:var(--mono);font-size:13.5px;color:var(--text-dim);letter-spacing:.01em}
.trust .dot{width:4px;height:4px;border-radius:50%;background:var(--text-dim)}
.trust b{color:var(--text-mid);font-weight:500}
.badges{display:flex;gap:10px;margin-bottom:26px;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;gap:8px;padding:8px 15px;border-radius:999px;
  background:rgba(255,255,255,.05);border:1px solid var(--border);font-size:14px;font-weight:600;color:var(--text-mid)}
.badge svg{width:15px;height:15px;color:var(--green)}

/* hero visual — player card */
.hero-art{position:relative}
.player-card{background:linear-gradient(165deg,var(--surface-2),var(--surface));
  border:1px solid var(--border-hi);border-radius:var(--r-xl);padding:26px;
  box-shadow:0 40px 90px -30px rgba(0,0,0,.8),var(--glow-violet)}
.player-card .cover{width:100%;aspect-ratio:1;border-radius:20px;overflow:hidden;position:relative}
.player-card .cover img{width:100%;height:100%;object-fit:cover}
.now-tag{position:absolute;top:14px;left:14px;display:inline-flex;align-items:center;gap:8px;
  padding:7px 13px;border-radius:999px;background:rgba(12,10,18,.6);backdrop-filter:blur(8px);
  font-family:var(--mono);font-size:12px;font-weight:500;color:#fff;border:1px solid var(--border-hi)}
.now-tag .eq{display:flex;align-items:flex-end;gap:2px;height:13px}
.now-tag .eq i{width:2.5px;background:var(--cyan);border-radius:2px;animation:eq 1s ease-in-out infinite}
.now-tag .eq i:nth-child(1){height:40%;animation-delay:0s}
.now-tag .eq i:nth-child(2){height:90%;animation-delay:.2s}
.now-tag .eq i:nth-child(3){height:60%;animation-delay:.4s}
.now-tag .eq i:nth-child(4){height:100%;animation-delay:.1s}
@keyframes eq{0%,100%{transform:scaleY(.4)}50%{transform:scaleY(1)}}
.float-chip{position:absolute;background:linear-gradient(160deg,var(--surface-hi),var(--surface-2));
  border:1px solid var(--border-hi);border-radius:16px;padding:13px 17px;display:flex;align-items:center;gap:11px;
  box-shadow:0 24px 50px -18px rgba(0,0,0,.7);font-size:14px;font-weight:600}
.float-chip .ic{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:var(--grad-soft)}
.float-chip .ic svg{width:17px;height:17px;color:var(--coral)}
.float-chip small{display:block;font-weight:500;color:var(--text-dim);font-size:12px}
.fc-1{top:-22px;right:-18px}
.fc-2{bottom:46px;left:-34px}

/* ---------- waveform divider ---------- */
.wave-div{display:flex;align-items:center;justify-content:center;gap:3px;height:54px;
  padding:0 32px;opacity:.6;overflow:hidden;mask-image:linear-gradient(90deg,transparent,#000 14%,#000 86%,transparent)}
.wave-div i{width:3px;border-radius:3px;background:linear-gradient(180deg,var(--coral),var(--violet));flex:0 0 auto}

/* ---------- generic card ---------- */
.card{background:linear-gradient(168deg,rgba(255,255,255,.062),rgba(255,255,255,.018));
  border:1px solid var(--border);border-radius:var(--r-lg);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.06);backdrop-filter:blur(10px);
  transition:transform .22s cubic-bezier(.2,.8,.2,1),border-color .22s,box-shadow .22s,background .22s}
.ic-tile{width:54px;height:54px;border-radius:15px;display:grid;place-items:center;
  background:var(--grad-soft);border:1px solid var(--border);margin-bottom:20px}
.ic-tile svg{width:25px;height:25px;color:var(--coral)}

/* УТП cards */
.utp-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.utp{padding:30px 26px 32px}
.utp h3{font-size:21px;font-weight:700;margin-bottom:10px;letter-spacing:-.02em}
.utp p{font-size:15.5px;color:var(--text-mid);line-height:1.55}
.utp:hover{transform:translateY(-5px);border-color:var(--border-hi);box-shadow:0 26px 60px -28px rgba(0,0,0,.7)}
.utp--key{background:linear-gradient(165deg,rgba(255,94,122,.1),rgba(180,92,255,.06));
  border-color:rgba(180,92,255,.35)}
.utp--key .ic-tile{background:var(--grad);border-color:transparent;box-shadow:var(--glow-coral)}
.utp--key .ic-tile svg{color:#fff}
.utp-tag{position:absolute;top:18px;right:18px;font-family:var(--mono);font-size:10.5px;font-weight:700;
  letter-spacing:.1em;text-transform:uppercase;color:var(--violet);
  padding:5px 10px;border-radius:999px;background:rgba(180,92,255,.14);border:1px solid rgba(180,92,255,.3)}

/* ---------- 3 steps ---------- */
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;position:relative}
.step{padding:34px 30px 36px;position:relative}
.step-n{font-family:var(--mono);font-weight:700;font-size:15px;width:46px;height:46px;border-radius:14px;
  display:grid;place-items:center;background:var(--surface-2);border:1px solid var(--border-hi);
  color:var(--violet);margin-bottom:22px}
.step h3{font-size:20px;font-weight:700;margin-bottom:11px;letter-spacing:-.02em}
.step p{font-size:15.5px;color:var(--text-mid);line-height:1.55}
.step-arrow{position:absolute;top:54px;right:-21px;z-index:2;color:var(--text-dim)}
.step-arrow svg{width:26px;height:26px}
.steps-cta{display:flex;align-items:center;justify-content:center;gap:22px;margin-top:46px;flex-wrap:wrap}
.steps-cta .link-note{font-size:16px;color:var(--text-mid)}

/* ---------- track examples ---------- */
.track-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.track{padding:16px;overflow:hidden}
.track:hover{transform:translateY(-5px);border-color:var(--border-hi);box-shadow:0 28px 64px -30px rgba(0,0,0,.75)}
.track-cover{position:relative;border-radius:16px;overflow:hidden;aspect-ratio:1}
.track-cover img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.track:hover .track-cover img{transform:scale(1.05)}
.play-fab{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);
  width:60px;height:60px;border-radius:50%;background:rgba(12,10,18,.5);backdrop-filter:blur(6px);
  border:1.5px solid rgba(255,255,255,.55);display:grid;place-items:center;cursor:pointer;
  transition:.2s;color:#fff}
.play-fab svg{width:24px;height:24px;margin-left:2px}
.track-cover:hover .play-fab{background:var(--grad);border-color:transparent;transform:translate(-50%,-50%) scale(1.08);box-shadow:var(--glow-coral)}
.track-stats{position:absolute;left:12px;bottom:12px;display:flex;gap:8px}
.tstat{display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:999px;
  background:rgba(12,10,18,.62);backdrop-filter:blur(8px);font-size:12.5px;font-weight:600;
  font-family:var(--mono);border:1px solid var(--border)}
.tstat svg{width:12px;height:12px;opacity:.85}
.track-body{padding:16px 6px 6px}
.track-title{font-size:17px;font-weight:700;letter-spacing:-.01em}
.track-author{font-size:14px;color:var(--text-dim);margin-top:2px}
.mini-player{display:flex;align-items:center;gap:12px;margin:15px 0 2px}
.mp-btn{width:40px;height:40px;border-radius:50%;flex:0 0 auto;display:grid;place-items:center;cursor:pointer;
  background:var(--surface-2);border:1px solid var(--border-hi);color:var(--text);transition:.18s}
.mp-btn:hover{background:var(--surface-hi);color:#fff}
.mp-btn svg{width:16px;height:16px}
.mp-progress{flex:1;display:flex;flex-direction:column;gap:6px}
.mp-bar{height:5px;border-radius:5px;background:rgba(255,255,255,.1);position:relative;overflow:hidden}
.mp-bar i{position:absolute;left:0;top:0;bottom:0;border-radius:5px;background:var(--grad)}
.mp-time{display:flex;justify-content:space-between;font-family:var(--mono);font-size:11.5px;color:var(--text-dim)}
.prompt-row{display:flex;gap:9px;margin-top:14px;padding:13px 14px;border-radius:13px;
  background:rgba(180,92,255,.07);border:1px solid rgba(180,92,255,.18)}
.prompt-row .pic{flex:0 0 auto;color:var(--violet);margin-top:1px}
.prompt-row .pic svg{width:15px;height:15px}
.prompt-row p{font-size:13.5px;line-height:1.5;color:var(--text-mid)}
.prompt-row b{color:var(--text);font-weight:600;font-family:var(--mono);font-size:11px;letter-spacing:.08em;
  text-transform:uppercase;display:block;margin-bottom:3px;color:var(--violet)}
/* track playing state */
.track--playing{border-color:rgba(78,217,232,.4);box-shadow:0 0 0 1px rgba(78,217,232,.2),0 24px 60px -30px rgba(0,0,0,.7)}
.track--playing .mp-btn{background:var(--grad);border-color:transparent;color:#fff}
.track--playing .mp-bar i{background:linear-gradient(90deg,var(--cyan),var(--violet))}

/* ---------- повод grid ---------- */
.povod-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
.povod{display:flex;align-items:center;gap:15px;padding:22px 22px;border-radius:var(--r);
  background:linear-gradient(168deg,rgba(255,255,255,.062),rgba(255,255,255,.018));backdrop-filter:blur(10px);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.06);border:1px solid var(--border);transition:.2s;position:relative;overflow:hidden}
.povod .pv-ic{width:46px;height:46px;border-radius:13px;flex:0 0 auto;display:grid;place-items:center;
  background:var(--grad-soft);border:1px solid var(--border);transition:.2s}
.povod .pv-ic svg{width:22px;height:22px;color:var(--coral);transition:.2s}
.povod span{font-size:16px;font-weight:600;letter-spacing:-.01em}
.povod .arr{margin-left:auto;color:var(--text-dim);opacity:0;transform:translateX(-6px);transition:.2s}
.povod .arr svg{width:18px;height:18px}
.povod:hover{transform:translateY(-4px);border-color:rgba(180,92,255,.4);background:var(--surface-2);
  box-shadow:0 22px 50px -26px rgba(0,0,0,.7)}
.povod:hover .pv-ic{background:var(--grad);border-color:transparent;box-shadow:var(--glow-coral)}
.povod:hover .pv-ic svg{color:#fff}
.povod:hover .arr{opacity:1;transform:translateX(0)}
.addressees{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:26px;justify-content:center}
.addressees .lbl{font-size:15px;color:var(--text-dim)}

/* ---------- chips (жанры / адресаты) ---------- */
.chip{display:inline-flex;align-items:center;padding:11px 19px;border-radius:999px;
  background:var(--surface);border:1px solid var(--border);font-size:15.5px;font-weight:600;
  color:var(--text-mid);transition:.18s;cursor:pointer;letter-spacing:-.01em}
.chip:hover{color:#fff;border-color:rgba(180,92,255,.5);background:var(--surface-2);transform:translateY(-2px)}
.chip:active{transform:translateY(0) scale(.97)}
.chip:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(180,92,255,.45)}
.chip--accent{background:var(--grad-soft);border-color:rgba(180,92,255,.35);color:var(--text)}
.chip-cloud{display:flex;flex-wrap:wrap;gap:13px;justify-content:center;max-width:1000px;margin:0 auto}

/* ---------- где использовать ---------- */
.use-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.use{padding:30px 28px 32px}
.use:hover{transform:translateY(-5px);border-color:var(--border-hi);box-shadow:0 26px 60px -28px rgba(0,0,0,.7)}
.use h3{font-size:20px;font-weight:700;margin-bottom:9px;letter-spacing:-.02em}
.use p{font-size:15.5px;color:var(--text-mid);line-height:1.55}

/* ---------- сравнение ---------- */
.cmp-intro{font-size:18px;color:var(--text-mid);line-height:1.6;max-width:64ch;margin:0 auto 44px;text-align:center}
.cmp{display:grid;grid-template-columns:1.1fr 1fr 1fr;border:1px solid var(--border);
  border-radius:var(--r-lg);overflow:hidden;background:var(--surface)}
.cmp-cell{padding:22px 26px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;font-size:16px}
.cmp-row{display:contents}
.cmp-crit{color:var(--text-mid);font-weight:600}
.cmp-them{color:var(--text-dim)}
.cmp-them .x{color:#E8718A;flex:0 0 auto}
.cmp-them svg,.cmp-us svg{width:19px;height:19px}
.cmp-us{color:var(--text);font-weight:600;background:linear-gradient(180deg,rgba(63,217,139,.07),rgba(63,217,139,.03))}
.cmp-us .ok{color:var(--green);flex:0 0 auto}
.cmp-head{font-weight:800;font-size:17px;letter-spacing:-.01em;padding-top:26px;padding-bottom:26px}
.cmp-head.cmp-us{position:relative}
.cmp-us-col{background:linear-gradient(180deg,rgba(63,217,139,.1),rgba(63,217,139,.02))}
.cmp .cmp-head.cmp-them{color:var(--text-mid)}
.cmp-badge{display:inline-flex;align-items:center;gap:7px}
.cmp-logo-dot{width:22px;height:22px;border-radius:7px;background:var(--grad);display:grid;place-items:center}
.cmp-logo-dot svg{width:11px;height:11px;color:#fff}

/* ---------- отзывы + счётчики ---------- */
.metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-bottom:56px}
.metric{text-align:center;padding:36px 24px;border-radius:var(--r-lg);
  background:linear-gradient(165deg,var(--surface-2),var(--surface));border:1px solid var(--border)}
.metric .num{font-size:54px;font-weight:900;letter-spacing:-.04em;line-height:1;
  background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.metric .lbl{font-size:16px;color:var(--text-mid);margin-top:12px}
.rev-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
.review{padding:30px 28px;display:flex;flex-direction:column}
.review .stars{display:flex;gap:3px;color:#FFCB52;margin-bottom:16px}
.review .stars svg{width:17px;height:17px}
.review p{font-size:16px;line-height:1.6;color:var(--text);flex:1}
.review .who{display:flex;align-items:center;gap:12px;margin-top:22px}
.review .who img{width:44px;height:44px;border-radius:50%;flex:0 0 auto}
.review .who b{font-size:15px;font-weight:700;display:block}
.review .who small{font-size:13px;color:var(--text-dim)}

/* ---------- тарифы ---------- */
.plans{display:grid;grid-template-columns:repeat(2,1fr);gap:24px;max-width:880px;margin:0 auto}
.plan{padding:38px 36px;display:flex;flex-direction:column;gap:6px;position:relative}
.plan h3{font-size:24px;font-weight:800;letter-spacing:-.02em}
.plan .price{font-size:40px;font-weight:900;letter-spacing:-.03em;margin:6px 0 4px}
.plan .price small{font-size:17px;font-weight:600;color:var(--text-dim)}
.plan .note{font-size:15px;color:var(--text-mid);margin-bottom:20px}
.plan ul{list-style:none;padding:0;margin:0 0 28px;display:flex;flex-direction:column;gap:13px}
.plan li{display:flex;gap:11px;align-items:flex-start;font-size:15.5px;color:var(--text-mid)}
.plan li svg{width:19px;height:19px;color:var(--green);flex:0 0 auto;margin-top:1px}
.plan .btn{margin-top:auto}
.plan--pro{background:linear-gradient(165deg,rgba(255,94,122,.1),rgba(180,92,255,.07));
  border-color:rgba(180,92,255,.4)}
.plan-flag{position:absolute;top:22px;right:24px;font-family:var(--mono);font-size:11px;font-weight:700;
  letter-spacing:.1em;text-transform:uppercase;color:#fff;padding:6px 12px;border-radius:999px;background:var(--grad)}

/* ---------- FAQ ---------- */
.faq{max-width:880px;margin:0 auto;display:flex;flex-direction:column;gap:14px}
.faq-item{border:1px solid var(--border);border-radius:var(--r);background:var(--surface);overflow:hidden;transition:.2s}
.faq-item:hover{border-color:var(--border-hi)}
.faq-q{display:flex;align-items:center;gap:18px;padding:24px 26px;cursor:pointer;list-style:none}
.faq-q::-webkit-details-marker{display:none}
.faq-q h3{font-size:18px;font-weight:600;letter-spacing:-.01em;flex:1}
.faq-ic{width:34px;height:34px;border-radius:10px;flex:0 0 auto;display:grid;place-items:center;
  background:var(--surface-2);border:1px solid var(--border-hi);color:var(--violet);transition:.25s}
.faq-ic svg{width:18px;height:18px;transition:.25s}
.faq-item[open]{background:var(--surface-2);border-color:rgba(180,92,255,.35)}
.faq-item[open] .faq-ic{background:var(--grad);border-color:transparent;color:#fff;transform:rotate(180deg)}
.faq-a{padding:0 26px 26px 78px;font-size:16px;line-height:1.62;color:var(--text-mid)}

/* ---------- финальный CTA ---------- */
.final{position:relative;overflow:hidden;border-radius:var(--r-xl);
  background:linear-gradient(120deg,rgba(255,94,122,.16),rgba(180,92,255,.16));
  border:1px solid rgba(180,92,255,.3);padding:80px 40px;text-align:center}
.final::before,.final::after{content:"";position:absolute;border-radius:50%;filter:blur(70px);opacity:.5;z-index:0}
.final::before{width:420px;height:420px;background:var(--coral);top:-180px;left:-80px}
.final::after{width:420px;height:420px;background:var(--violet);bottom:-200px;right:-60px}
.final>*{position:relative;z-index:1}
.final h2{font-size:50px;font-weight:900;letter-spacing:-.03em;line-height:1.05}
.final p{font-size:20px;color:var(--text-mid);margin:18px auto 36px;max-width:46ch}
.final-wave{display:flex;align-items:center;justify-content:center;gap:4px;height:42px;margin-bottom:30px}
.final-wave i{width:4px;border-radius:4px;background:var(--grad)}

/* ---------- SEO text ---------- */
.seo{max-width:820px;margin:0 auto;display:flex;flex-direction:column;gap:20px}
.seo h2{font-size:30px;font-weight:800;letter-spacing:-.02em;margin-bottom:8px}
.seo p{font-size:16px;line-height:1.7;color:var(--text-dim)}
.seo p b{color:var(--text-mid);font-weight:600}

/* ---------- footer ---------- */
.ftr{border-top:1px solid var(--border);padding:60px 0 40px;margin-top:20px;background:var(--bg-2)}
.ftr-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:40px;margin-bottom:44px}
.ftr-about{max-width:34ch;color:var(--text-dim);font-size:15px;line-height:1.6;margin-top:16px}
.ftr h4{font-size:13px;font-family:var(--mono);letter-spacing:.12em;text-transform:uppercase;
  color:var(--text-dim);margin-bottom:18px;font-weight:500}
.ftr ul{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:12px}
.ftr a{color:var(--text-mid);font-size:15px;transition:.16s}
.ftr a:hover{color:var(--text)}
.ftr-bot{display:flex;justify-content:space-between;align-items:center;padding-top:28px;
  border-top:1px solid var(--border);color:var(--text-dim);font-size:13.5px;flex-wrap:wrap;gap:14px}

/* header graceful compression above the mobile pass (keeps primary CTA visible) */
@media (max-width:1200px){
  .hdr-in{gap:18px}
  .nav{margin-left:0;gap:2px}
  .nav a{padding:9px 11px;font-size:14.5px}
}
@media (max-width:1080px){
  .nav{display:none}
  .hdr-cta{margin-left:auto}
}

/* ---------- states showcase (design handoff) ---------- */
.states{background:var(--bg-2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.states .h-sec{font-size:38px}
.spec-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:22px;margin-top:44px}
.spec{padding:28px 30px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg)}
.spec-h{font-family:var(--mono);font-size:12px;letter-spacing:.1em;text-transform:uppercase;
  color:var(--violet);margin-bottom:22px;display:flex;align-items:center;gap:9px}
.spec-h::before{content:"";width:18px;height:2px;background:var(--grad);border-radius:2px}
.spec-row{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.spec-tag{font-family:var(--mono);font-size:11px;color:var(--text-dim);min-width:74px}
.spec-line{display:flex;align-items:center;gap:14px;padding:10px 0;flex-wrap:wrap}
.spec-line+.spec-line{border-top:1px dashed var(--border)}
.note-cmt{font-family:var(--mono);font-size:12.5px;color:var(--text-dim);background:rgba(255,203,82,.06);
  border:1px dashed rgba(255,203,82,.3);border-radius:10px;padding:12px 15px;margin-top:14px;line-height:1.55}
.note-cmt b{color:#FFCB52;font-weight:700}

/* entrance: gated on .js so content is always visible without JS,
   in print, and in static captures (PDF export safe) */
.reveal{opacity:1;transform:none}
@media (prefers-reduced-motion:no-preference){
  html.js .reveal{opacity:0;transform:translateY(22px);animation:rise .7s cubic-bezier(.2,.8,.2,1) forwards}
  @keyframes rise{to{opacity:1;transform:none}}
}
@media print{.reveal{opacity:1!important;transform:none!important;animation:none!important}}

/* ============================================================
   REDESIGN v2 — product mockup, marquee, glass
   ============================================================ */

/* hero product stack */
.hero-stack{position:relative;perspective:1600px}
.hero-glow{position:absolute;inset:-40px -20px;z-index:0;pointer-events:none}
.hero-glow::before,.hero-glow::after{content:"";position:absolute;border-radius:50%;filter:blur(80px);opacity:.34}
.hero-glow::before{width:280px;height:280px;background:rgba(255,94,122,.9);top:0;right:-10px}
.hero-glow::after{width:300px;height:300px;background:rgba(180,92,255,.9);bottom:-30px;right:60px}
.hero-stack>*{position:relative;z-index:1}

/* generator panel mockup */
.gen-card{border-radius:28px;padding:22px 22px 24px;
  background:linear-gradient(168deg,rgba(255,255,255,.09),rgba(255,255,255,.028));
  border:1px solid var(--border-hi);backdrop-filter:blur(22px);
  box-shadow:0 44px 100px -34px rgba(0,0,0,.85),inset 0 1px 0 rgba(255,255,255,.12);}
.gen-top{display:flex;align-items:center;gap:11px;margin-bottom:18px}
.gen-top .gd{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;background:var(--grad);box-shadow:var(--glow-coral)}
.gen-top .gd svg{width:18px;height:18px;color:#fff}
.gen-top b{font-size:16.5px;font-weight:700;letter-spacing:-.01em}
.gen-top small{display:block;font-size:12.5px;color:var(--text-dim);font-weight:500}
.gen-tabs{display:flex;gap:5px;background:rgba(0,0,0,.28);padding:5px;border-radius:14px;margin-bottom:16px}
.gen-tab{flex:1;text-align:center;padding:10px;border-radius:10px;font-size:14px;font-weight:600;color:var(--text-dim)}
.gen-tab.on{background:var(--surface-hi);color:#fff;box-shadow:0 3px 10px rgba(0,0,0,.35),inset 0 1px 0 rgba(255,255,255,.08)}
.gen-lbl{font-family:var(--mono);font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--text-dim);margin:0 0 9px}
.gen-prompt{background:rgba(0,0,0,.24);border:1px solid var(--border);border-radius:14px;padding:14px 15px;
  font-size:14.5px;color:var(--text-mid);line-height:1.5;margin-bottom:18px;min-height:74px}
.gen-prompt .cur{display:inline-block;width:2px;height:1.05em;background:var(--coral);vertical-align:-2px;margin-left:1px;animation:blink 1.1s steps(1) infinite}
@keyframes blink{50%{opacity:0}}
.gen-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px}
.gen-chip{padding:8px 14px;border-radius:999px;font-size:13.5px;font-weight:600;background:rgba(255,255,255,.05);border:1px solid var(--border);color:var(--text-mid)}
.gen-chip.on{background:var(--grad-soft);border-color:rgba(180,92,255,.45);color:#fff}
.gen-voice{display:flex;gap:8px;margin-bottom:20px}
.gen-seg{flex:1;text-align:center;padding:11px;border-radius:12px;font-size:14px;font-weight:600;
  border:1px solid var(--border);background:rgba(255,255,255,.04);color:var(--text-mid);display:flex;align-items:center;justify-content:center;gap:7px}
.gen-seg svg{width:15px;height:15px}
.gen-seg.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:var(--glow-coral)}
.gen-go{width:100%;border:none;border-radius:15px;padding:16px;font-family:var(--font);font-weight:700;font-size:16px;
  color:#fff;background:var(--grad);box-shadow:var(--glow-coral);display:flex;align-items:center;justify-content:center;gap:9px}
.gen-go svg{width:18px;height:18px}

/* now-playing floating bar */
.np-bar{position:absolute;left:-30px;bottom:-26px;right:34px;z-index:3;display:flex;align-items:center;gap:13px;
  padding:12px 14px;border-radius:18px;backdrop-filter:blur(20px);
  background:linear-gradient(165deg,rgba(38,29,59,.92),rgba(23,17,33,.92));
  border:1px solid var(--border-hi);box-shadow:0 26px 56px -22px rgba(0,0,0,.8)}
.np-bar .npc{width:46px;height:46px;border-radius:12px;overflow:hidden;flex:0 0 auto}
.np-bar .npc img{width:100%;height:100%;object-fit:cover}
.np-meta{flex:1;min-width:0}
.np-meta b{font-size:14px;font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.np-meta .npw{display:flex;align-items:flex-end;gap:2px;height:13px;margin-top:5px}
.np-meta .npw i{width:2.5px;border-radius:2px;background:var(--cyan);animation:eq 1s ease-in-out infinite}
.np-meta .npw i:nth-child(odd){background:var(--violet)}
.np-play{width:42px;height:42px;border-radius:50%;flex:0 0 auto;display:grid;place-items:center;background:var(--grad);color:#fff;box-shadow:var(--glow-coral)}
.np-play svg{width:18px;height:18px}

/* floating glass chip (reused) */
.float-chip{backdrop-filter:blur(16px);background:linear-gradient(160deg,rgba(38,29,59,.85),rgba(30,23,48,.85))}

/* ---------- marquee of covers ---------- */
.marquee-sec{padding:30px 0 8px}
.marquee{overflow:hidden;padding:6px 0;
  -webkit-mask:linear-gradient(90deg,transparent,#000 7%,#000 93%,transparent);
  mask:linear-gradient(90deg,transparent,#000 7%,#000 93%,transparent)}
.marquee-track{display:flex;gap:18px;width:max-content;animation:scrollx 46s linear infinite}
.marquee.rev .marquee-track{animation-direction:reverse;animation-duration:54s}
.marquee:hover .marquee-track{animation-play-state:paused}
.mq{width:152px;flex:0 0 auto}
.mq-cover{width:152px;height:152px;border-radius:18px;overflow:hidden;position:relative;
  border:1px solid var(--border);box-shadow:0 18px 40px -22px rgba(0,0,0,.8)}
.mq-cover img{width:100%;height:100%;object-fit:cover}
.mq-cover::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(8,6,14,.8));}
.mq-cover .mqp{position:absolute;left:11px;bottom:10px;right:11px;z-index:2}
.mq-cover .mqp b{font-size:13px;font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mq-cover .mqp span{font-size:11px;color:var(--text-mid)}
@keyframes scrollx{to{transform:translateX(calc(-50% - 9px))}}
@media (prefers-reduced-motion:reduce){.marquee-track{animation:none}}

/* hero stacks gracefully below desktop (before the mobile pass) */
@media (max-width:1080px){
  .hero{padding:60px 0 80px}
  .hero-grid{grid-template-columns:1fr;gap:44px}
  .hero h1{font-size:54px}
  .hero-copy{max-width:640px}
  .hero-art{max-width:440px}
  .np-bar{left:0;right:46px}
}
@media (max-width:560px){
  .hero h1{font-size:40px}
  .wrap{padding:0 20px}
  .hero-cta{flex-direction:column;align-items:stretch}
  .hero-cta .btn{width:100%}
}

/* ---------- chat-style hero (tunee-like conversational agent) ---------- */
.chat-card{border-radius:28px;overflow:hidden;
  background:linear-gradient(168deg,rgba(255,255,255,.075),rgba(255,255,255,.022));
  border:1px solid var(--border-hi);backdrop-filter:blur(22px);
  box-shadow:0 44px 100px -34px rgba(0,0,0,.85),inset 0 1px 0 rgba(255,255,255,.12)}
.chat-head{display:flex;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid var(--border)}
.chat-head .ava{width:38px;height:38px;border-radius:50%;background:var(--grad);display:grid;place-items:center;box-shadow:var(--glow-coral)}
.chat-head .ava svg{width:18px;height:18px;color:#fff}
.chat-head b{font-size:15px;font-weight:700;display:block;letter-spacing:-.01em}
.chat-head .stat{font-size:12px;color:var(--green);font-weight:600;display:flex;align-items:center;gap:6px}
.chat-head .stat::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 8px var(--green)}
.chat-head .mdl{margin-left:auto;font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;
  color:var(--text-dim);padding:6px 10px;border-radius:999px;border:1px solid var(--border)}
.chat-body{padding:20px 18px;display:flex;flex-direction:column;gap:14px}
.msg{max-width:84%;font-size:14.5px;line-height:1.5;padding:13px 16px;border-radius:18px}
.msg.me{align-self:flex-end;background:var(--grad);color:#fff;border-bottom-right-radius:6px;box-shadow:var(--glow-coral)}
.msg.ai{align-self:flex-start;background:rgba(255,255,255,.06);border:1px solid var(--border);
  border-bottom-left-radius:6px;color:var(--text)}
.msg.ai .typing{display:inline-flex;gap:4px;vertical-align:middle;margin-left:2px}
.msg.ai .typing i{width:6px;height:6px;border-radius:50%;background:var(--text-dim);animation:tdot 1.2s infinite}
.msg.ai .typing i:nth-child(2){animation-delay:.2s}.msg.ai .typing i:nth-child(3){animation-delay:.4s}
@keyframes tdot{0%,60%,100%{opacity:.3;transform:translateY(0)}30%{opacity:1;transform:translateY(-3px)}}
.chat-track{align-self:flex-start;width:88%;display:flex;align-items:center;gap:13px;padding:12px;border-radius:16px;
  background:linear-gradient(160deg,rgba(38,29,59,.7),rgba(23,17,33,.7));border:1px solid var(--border-hi)}
.chat-track .ct-cov{width:52px;height:52px;border-radius:12px;overflow:hidden;flex:0 0 auto}
.chat-track .ct-cov img{width:100%;height:100%;object-fit:cover}
.chat-track .ct-meta{flex:1;min-width:0}
.chat-track .ct-meta b{font-size:14px;font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-track .ct-meta small{font-size:12px;color:var(--text-dim)}
.chat-track .ct-wave{display:flex;align-items:center;gap:2px;height:20px;margin-top:6px}
.chat-track .ct-wave i{width:2.5px;border-radius:2px;background:var(--violet)}
.chat-track .ct-wave i:nth-child(even){background:var(--cyan)}
.chat-track .ct-play{width:40px;height:40px;border-radius:50%;flex:0 0 auto;display:grid;place-items:center;background:var(--grad);color:#fff;box-shadow:var(--glow-coral)}
.chat-track .ct-play svg{width:17px;height:17px}
.chat-input{display:flex;align-items:center;gap:10px;padding:14px 16px;border-top:1px solid var(--border)}
.chat-input .ci-field{flex:1;font-size:14px;color:var(--text-dim);padding:11px 15px;border-radius:13px;
  background:rgba(0,0,0,.24);border:1px solid var(--border)}
.chat-input .ci-field .cur{display:inline-block;width:2px;height:1.05em;background:var(--coral);vertical-align:-2px;animation:blink 1.1s steps(1) infinite}
.chat-input .ci-send{width:44px;height:44px;border-radius:13px;flex:0 0 auto;display:grid;place-items:center;background:var(--grad);color:#fff;box-shadow:var(--glow-coral)}
.chat-input .ci-send svg{width:18px;height:18px}

/* tunee-style micro spec caption on cards */
.spec-cap{font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;
  color:var(--violet);margin-top:16px;padding-top:14px;border-top:1px solid var(--border)}

/* ============================================================
   LIGHT THEME — override layer (clean, warm, premium)
   ============================================================ */
:root{
  --bg:#F4F1F8;
  --bg-2:#ECE7F4;
  --surface:#FFFFFF;
  --surface-2:#F5F1FA;
  --surface-hi:#ECE5F6;
  --border:rgba(26,16,48,.10);
  --border-hi:rgba(26,16,48,.18);
  --text:#19112A;
  --text-mid:#574E6B;
  --text-dim:#8A8199;
  --coral:#F5395F;
  --violet:#8B33E0;
  --cyan:#0FACBD;
  --green:#13A35F;
  --grad:linear-gradient(98deg,#F5395F 0%,#9B3CF0 100%);
  --grad-soft:linear-gradient(98deg,rgba(245,57,95,.12),rgba(155,60,240,.12));
  --glow-coral:0 16px 38px -16px rgba(245,57,95,.42);
  --glow-violet:0 18px 44px -18px rgba(139,51,224,.36);
}
body{
  color:var(--text);
  background:
    radial-gradient(900px 640px at 84% -12%, rgba(155,60,240,.12), transparent 62%),
    radial-gradient(820px 560px at 2% 0%, rgba(245,57,95,.09), transparent 58%),
    var(--bg);
}
::selection{background:rgba(139,51,224,.22);color:var(--text)}

/* header */
.hdr{background:rgba(244,241,248,.8);border-bottom:1px solid var(--border)}
.nav a:hover{background:rgba(26,16,48,.05)}

/* cards → solid white with soft shadow */
.card{background:#fff;backdrop-filter:none;box-shadow:0 14px 36px -22px rgba(30,18,60,.28),inset 0 1px 0 rgba(255,255,255,.6)}
.card:hover,.utp:hover,.use:hover,.track:hover{box-shadow:0 30px 60px -30px rgba(30,18,60,.32)}
.povod{background:#fff;backdrop-filter:none;box-shadow:0 14px 32px -22px rgba(30,18,60,.22)}
.povod:hover{background:#fff;box-shadow:0 26px 52px -28px rgba(139,51,224,.3)}
.metric{background:linear-gradient(165deg,#fff,#F6F2FB);box-shadow:0 16px 38px -24px rgba(30,18,60,.24)}

/* badges / ghost button / chips */
.badge{background:#fff;border-color:var(--border)}
.btn--ghost{background:#fff;border:1.5px solid var(--border-hi);color:var(--text)}
.btn--ghost:hover{background:var(--surface-2);border-color:var(--text-dim)}
.chip{background:#fff}
.chip:hover{background:var(--surface-2)}

/* hero glow softer */
.hero-glow::before,.hero-glow::after{opacity:.16;filter:blur(92px)}

/* chat card → white */
.chat-card{background:#fff;backdrop-filter:none;
  box-shadow:0 48px 100px -46px rgba(30,18,60,.45),0 6px 20px -10px rgba(30,18,60,.12)}
.msg.ai{background:var(--surface-2);border-color:var(--border)}
.chat-track{background:linear-gradient(160deg,#FBF9FE,#F2EDFA);border-color:var(--border)}
.chat-input .ci-field{background:var(--surface-2);border-color:var(--border)}

/* floating chip → white glass */
.float-chip{backdrop-filter:none;background:#fff;border:1px solid var(--border);
  box-shadow:0 22px 46px -22px rgba(30,18,60,.3)}

/* mini player track on light */
.mp-bar{background:rgba(26,16,48,.1)}

/* final banner on light */
.final{background:linear-gradient(120deg,rgba(245,57,95,.1),rgba(155,60,240,.12));
  border-color:rgba(139,51,224,.22)}
.final::before,.final::after{opacity:.22}
.final h2{color:var(--text)}

/* footer */
.ftr{background:var(--bg-2)}

/* marquee cover shadow lighter; caption text on dark covers stays light */
.mq-cover{box-shadow:0 18px 38px -24px rgba(30,18,60,.4)}
.mq-cover .mqp b{color:#fff}
.mq-cover .mqp span{color:rgba(255,255,255,.78)}

/* comparison: keep us-column green tint readable on light */
.cmp-us{background:linear-gradient(180deg,rgba(19,163,95,.09),rgba(19,163,95,.03))}
.cmp-us-col{background:linear-gradient(180deg,rgba(19,163,95,.12),rgba(19,163,95,.03))}
.cmp-them .x{color:#E0556F}

/* counters sit on dark cover art → keep white in light theme */
.tstat{color:rgba(255,255,255,.92)}

/* ============================================================
   THINNER TYPE + card content alignment
   ============================================================ */
h1,h2,h3,h4{font-weight:600}
.hero h1,.h-sec,.cmp-head,.plan h3,.seo h2,.final h2,.metric .num,.plan .price{font-weight:600}
.utp h3,.step h3,.use h3,.track-title,.review .who b,
.chat-head b,.chat-track .ct-meta b,.mq-cover .mqp b,.np-meta b,.gen-top b{font-weight:600}
.btn,.gen-go{font-weight:600}
.logo,.logo b{font-weight:700}
.nav a,.link-login,.badge,.chip,.cmp-crit,.cmp-us,.faq-q h3,
.utp-tag,.step-n,.plan-flag,.spec-cap,.metric .lbl{font-weight:500}
.hero-sub,.sub-sec,.utp p,.step p,.use p,.seo p,.review p,.msg{font-weight:400}

/* align card content: pin the mono caption to the bottom so all cards line up */
.utp{display:flex;flex-direction:column}
.utp .spec-cap{margin-top:auto}

/* real logo + b1-style tilted framed cover in hero */
.logo-img{height:30px;width:auto;display:block}
.hero-glow{z-index:0}
.hero-frame{position:absolute;z-index:1;top:-34px;left:-40px;width:198px;transform:rotate(-9deg);
  border-radius:18px;overflow:hidden;border:7px solid #fff;
  box-shadow:0 28px 60px -18px rgba(20,12,40,.45)}
.hero-frame img{display:block;width:100%;height:auto}
.chat-card{position:relative;z-index:3}
.float-chip{z-index:4}
@media (max-width:560px){.hero-frame{display:none}}

/* ============================================================
   BRAND PALETTE — match the live site (blue CTA + pink→indigo)
   ============================================================ */
:root{
  --coral:#3B5BDB;            /* gradient start — blue */
  --violet:#141C4F;           /* gradient end — deep navy */
  --blue:#2F7BEF;             /* primary action */
  --blue-d:#2466DA;
  --grad:linear-gradient(105deg,#3B5BDB 0%,#141C4F 100%);
  --grad-soft:linear-gradient(105deg,rgba(59,91,219,.14),rgba(20,28,79,.12));
  --glow-coral:0 16px 38px -16px rgba(20,28,79,.42);
  --glow-violet:0 18px 44px -18px rgba(20,28,79,.36);
}
body{
  background:
    radial-gradient(900px 640px at 84% -12%, rgba(59,91,219,.13), transparent 62%),
    radial-gradient(820px 560px at 2% 0%, rgba(20,28,79,.08), transparent 58%),
    var(--bg);
}
::selection{background:rgba(59,91,219,.22);color:var(--text)}

/* primary CTA → solid royal blue (like the site) */
.btn--primary{background:var(--blue);color:#fff;box-shadow:0 16px 38px -16px rgba(47,123,239,.5)}
.btn--primary:hover{background:var(--blue-d);transform:translateY(-2px);box-shadow:0 22px 50px -12px rgba(47,123,239,.6)}
.btn--primary:active{background:var(--blue-d);transform:translateY(0) scale(.98)}
.btn--primary:focus-visible{box-shadow:0 16px 38px -16px rgba(47,123,239,.5),0 0 0 4px rgba(47,123,239,.38)}
.gen-go,.ci-send,.np-play,.ct-play,.mp-btn--on{background:var(--blue)}

/* focus rings → blue */
.btn--ghost:focus-visible{box-shadow:0 0 0 4px rgba(47,123,239,.34)}
.chip:focus-visible{box-shadow:0 0 0 3px rgba(47,123,239,.38)}

/* re-tint accents to the deep-navy brand tone */
.utp--key{background:linear-gradient(165deg,rgba(59,91,219,.1),rgba(20,28,79,.06));border-color:rgba(59,91,219,.32)}
.utp-tag{color:#3B5BDB;background:rgba(59,91,219,.12);border-color:rgba(59,91,219,.3)}
.povod:hover{border-color:rgba(59,91,219,.42)}
.chip:hover{border-color:rgba(59,91,219,.5)}
.faq-item[open]{border-color:rgba(59,91,219,.35)}
.plan--pro{background:linear-gradient(165deg,rgba(59,91,219,.1),rgba(20,28,79,.07));border-color:rgba(59,91,219,.42)}
.prompt-row{background:rgba(59,91,219,.08);border-color:rgba(59,91,219,.2)}
.hero-glow::before{background:rgba(59,91,219,.9)}
.hero-glow::after{background:rgba(20,28,79,.9)}
.final{background:linear-gradient(120deg,rgba(59,91,219,.1),rgba(20,28,79,.12));border-color:rgba(59,91,219,.24)}
.final::before{background:#3B5BDB}
.final::after{background:#141C4F}

/* hero image (b1) */
.hero-b1{width:100%;height:auto;display:block;position:relative;z-index:2;filter:drop-shadow(0 26px 50px rgba(20,28,79,.35))}

/* ============================================================
   LIVE-SITE TUNING — pure-white bg, navy header/footer, lilac blocks
   ============================================================ */
:root{
  --bg:#FFFFFF;
  --bg-2:#F3F2FB;                 /* live pale lilac */
  --navy-grad:linear-gradient(120deg,#0E1330 0%,#1A1640 55%,#2A1840 100%);
  --ftr-grad:linear-gradient(95deg,#141A33 0%,#241433 55%,#3A1230 100%);
}
/* pure white page — no ambient gradients */
body{background:#FFFFFF}

/* dark navy header (current site format) */
.hdr{background:var(--navy-grad);border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:none}
.hdr .nav a{color:rgba(255,255,255,.78)}
.hdr .nav a:hover{color:#fff;background:rgba(255,255,255,.08)}
.hdr .link-login{color:rgba(255,255,255,.85)}
.hdr .link-login:hover{color:#fff}
.hdr .logo-img{filter:brightness(0) invert(1)}     /* white logo on navy */

/* lilac alternating sections (like live site) stay as --bg-2 */
.utp--key{background:linear-gradient(165deg,rgba(59,91,219,.08),rgba(20,28,79,.05))}

/* dark navy footer (current site format) */
.ftr{background:var(--ftr-grad);color:rgba(255,255,255,.7)}
.ftr .logo-img{filter:brightness(0) invert(1)}
.ftr h4{color:rgba(255,255,255,.5)}
.ftr a{color:rgba(255,255,255,.72)}
.ftr a:hover{color:#fff}
.ftr-about{color:rgba(255,255,255,.6)}
.ftr-bot{color:rgba(255,255,255,.5);border-top-color:rgba(255,255,255,.1)}
</style>
</head>
<body>



<!-- ======================= ICON SPRITE ======================= -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <g id="i-play"><path d="M7 5.5v13a1 1 0 0 0 1.5.87l11-6.5a1 1 0 0 0 0-1.74l-11-6.5A1 1 0 0 0 7 5.5Z" fill="currentColor" stroke="none"></path></g>
    <g id="i-pause" fill="currentColor" stroke="none"><rect x="6" y="5" width="4" height="14" rx="1.3"></rect><rect x="14" y="5" width="4" height="14" rx="1.3"></rect></g>
    <g id="i-mic" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="11" rx="3"></rect><path d="M5 11a7 7 0 0 0 14 0M12 18v3"></path></g>
    <g id="i-shield" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 5-3.5 8.2-7 9.5C8.5 19.2 5 16 5 11V6l7-3Z"></path><path d="M9 12l2 2 4-4"></path></g>
    <g id="i-bolt" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"></path></g>
    <g id="i-globe" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3c2.5 2.5 3.8 5.8 3.8 9S14.5 18.5 12 21c-2.5-2.5-3.8-5.8-3.8-9S9.5 5.5 12 3Z"></path></g>
    <g id="i-spark" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.8 4.9L19 9.7l-5.2 1.8L12 16.4l-1.8-4.9L5 9.7l5.2-1.8L12 3Z"></path><path d="M19 14l.7 1.9L22 16.5l-2.3.6L19 19l-.7-1.9L16 16.5l2.3-.6L19 14Z"></path></g>
    <g id="i-gift" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="9" width="16" height="11" rx="1.5"></rect><path d="M4 13h16M12 9v11M12 9c-1.5-3.5-5.5-3.5-5.5-1 0 1.5 2.5 1 5.5 1Zm0 0c1.5-3.5 5.5-3.5 5.5-1 0 1.5-2.5 1-5.5 1Z"></path></g>
    <g id="i-thumb" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v10M7 10l4-7a2 2 0 0 1 2 2v3h5l-1.5 7H7M3 10h4v10H3V10Z"></path></g>
    <g id="i-rings" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="14" r="6"></circle><circle cx="15" cy="14" r="6"></circle><path d="M9 4l3 3 3-3M12 7V3"></path></g>
    <g id="i-cal" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="2"></rect><path d="M3.5 10h17M8 3v4M16 3v4"></path><circle cx="12" cy="15" r="1.6" fill="currentColor" stroke="none"></circle></g>
    <g id="i-balloon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c3.3 0 6 2.6 6 6 0 4-3.5 6.6-6 6.6S6 13 6 9c0-3.4 2.7-6 6-6Z"></path><path d="M12 15.6v2M12 17.6c0 1.4 1.6 1.4 1.6 2.8"></path></g>
    <g id="i-star" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.7 5.6 6.1.8-4.5 4.2 1.2 6L12 16.9 6.5 19.6l1.2-6L3.2 9.4l6.1-.8L12 3Z"></path></g>
    <g id="i-star-f" fill="currentColor" stroke="none"><path d="M12 3l2.7 5.6 6.1.8-4.5 4.2 1.2 6L12 16.9 6.5 19.6l1.2-6L3.2 9.4l6.1-.8L12 3Z"></path></g>
    <g id="i-snow" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M4.5 7l15 10M19.5 7l-15 10"></path><path d="M12 6l2.2-2M12 6 9.8 4M12 18l2.2 2M12 18l-2.2 2M6 9.7 3.3 9M6 9.7l-.6-2.7M18 14.3l2.7.7M18 14.3l.6 2.7M18 9.7l2.7-.7M18 9.7l-.6-2.7M6 14.3l-2.7.7M6 14.3l.6 2.7"></path></g>
    <g id="i-cap" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4 2.5 9 12 14l9.5-5L12 4Z"></path><path d="M6 11v5c0 1.2 2.7 2.5 6 2.5s6-1.3 6-2.5v-5M21.5 9v5"></path></g>
    <g id="i-check" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 6.5"></path></g>
    <g id="i-x" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"></path></g>
    <g id="i-arr" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"></path></g>
    <g id="i-chev" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></g>
    <g id="i-head" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13v-1a8 8 0 0 1 16 0v1"></path><rect x="3" y="13" width="4.5" height="7" rx="2"></rect><rect x="16.5" y="13" width="4.5" height="7" rx="2"></rect></g>
    <g id="i-note" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V6l11-2v12"></path><circle cx="6" cy="18" r="3"></circle><circle cx="17" cy="16" r="3"></circle></g>
    <g id="i-quote" fill="currentColor" stroke="none"><path d="M9 6c-3 1-5 3.7-5 7v5h6v-6H6.5C6.6 9.6 7.7 8 9.5 7.2L9 6Zm9 0c-3 1-5 3.7-5 7v5h6v-6h-3.5c.1-2.4 1.2-4 3-4.8L18 6Z"></path></g>
    <g id="i-brief" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7.5" width="18" height="12.5" rx="2"></rect><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5M3 13h18"></path></g>
    <g id="i-mega" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10v4a2 2 0 0 0 2 2h2l9 4V4l-9 4H6a2 2 0 0 0-2 2Z"></path><path d="M19 9a3.5 3.5 0 0 1 0 6"></path></g>
    <g id="i-palette" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 0 18c1.4 0 2-1 2-2 0-1.5 1-2 2.2-2H18a3 3 0 0 0 3-3c0-5-4-8-9-8Z"></path><circle cx="8" cy="11" r="1.1" fill="currentColor" stroke="none"></circle><circle cx="12" cy="8" r="1.1" fill="currentColor" stroke="none"></circle><circle cx="16" cy="10" r="1.1" fill="currentColor" stroke="none"></circle></g>
    <g id="i-users" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"></circle><path d="M3.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5M16 5.2A3.2 3.2 0 0 1 16 11M17 14c2.4.4 4 2.3 4 5"></path></g>
    <g id="i-wallet" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2.5"></rect><path d="M3 10h18M16 14h2"></path></g>
    <g id="i-lang" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h9M8.5 5v2.5c0 3.5-2 6.5-5 8M6 10c.5 2.5 3 5 6 6"></path><path d="M13 20l4-9 4 9M14.6 16.5h4.8"></path></g>
    <g id="i-down" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 11l5 5 5-5M5 21h14"></path></g>
    <g id="i-zap" fill="currentColor" stroke="none"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"></path></g>
    <g id="i-send" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 3 10 14M21 3l-6.5 18-4-8-8-4L21 3Z"></path></g>
  </defs>
</svg>

<!-- ======================= MOBILE DRAWER ======================= -->
<div class="mob-overlay" id="mobOverlay" aria-hidden="true"></div>
<nav class="mob-drawer" id="mobDrawer" aria-hidden="true">
  <button class="mob-close" id="mobClose" aria-label="Закрыть меню">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"></path></svg>
  </button>
  <ul class="mob-nav">
    <li><a href="/">Главная</a></li>
    <li><a href="/articles">Статьи</a></li>
    <li><a href="/pages/povod-dlya-pesni/den-rozhdeniya">Повод для песни</a></li>
    <li><a href="/help">Помощь</a></li>
    <li><a href="/tarify">Тарифы</a></li>
    <li><a href="/create-song">Создать трек</a></li>
    @if(isset($authUser) && $authUser)
    <li><a href="{{ route('dashboard') }}">Личный кабинет</a></li>
    @else
    <li><a href="/login">Вход</a></li>
    <li><a href="/register">Регистрация</a></li>
    @endif
  </ul>
</nav>

<!-- ======================= HEADER ======================= -->
<header class="hdr">
  <div class="wrap hdr-in">
    <button class="mob-burger" id="mobBurger" aria-label="Открыть меню">
      <span></span><span></span><span></span>
    </button>
    <a href="/" class="logo">
      <img src="/static/landing2/logo.svg" alt="На Репите" class="logo-img">
    </a>
    <nav class="nav">
      <a href="/">Главная</a>
      <a href="/articles">Статьи</a>
      <a href="/pages/povod-dlya-pesni/den-rozhdeniya">Повод для песни</a>
      <a href="/tarify">Тарифы</a>
      <a href="#faq">FAQ</a>
      <a href="/help">Помощь</a>
    </nav>
    <div class="hdr-cta">
      @if(isset($authUser) && $authUser)
        <a href="{{ route('dashboard') }}" class="btn btn--primary btn--sm">Личный кабинет</a>
      @else
        <a href="/login" class="link-login">Вход</a>
        <a href="/register" class="btn btn--primary btn--sm">Регистрация</a>
      @endif
    </div>
  </div>
</header>

<!-- ======================= 1. HERO ======================= -->
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy reveal">

      <h1>Нейросеть для создания песен: <span class="em">генерация музыки и текста</span> онлайн</h1>
      <p class="hero-sub">Создавайте уникальные песни с живым русским вокалом — без акцента, без VPN и без регистрации. Опишите идею, а ИИ напишет текст, подберёт музыку и споёт. Оплата — 199&nbsp;₽ за готовую песню.</p>
      <div class="hero-cta">
        <a href="/create-song" class="btn btn--primary btn--lg">
          <svg viewBox="0 0 24 24"><use href="#i-spark"></use></svg>Создать трек</a>
        <a href="#examples" class="btn btn--ghost btn--lg">
          <svg viewBox="0 0 24 24"><use href="#i-head"></use></svg>Послушать примеры</a>
      </div>
      <div class="trust">
        <span>Уже создано <b>более {{ number_format(intdiv($songsTotal ?? 39000, 1000) * 1000, 0, '', ' ') }} песен</b></span><span class="dot"></span>
        <span><b>1000+</b> голосов</span><span class="dot"></span>
        <span>от попа до шансона</span>
      </div>
    </div>

    <div class="hero-art reveal hero-stack" style="animation-delay:.1s">
      <div class="hero-glow"></div>
      <img class="hero-b1" src="/static/landing2/cassette.png" alt="Кассета На Репите">

      <div class="float-chip fc-1">
        <span class="ic"><svg viewBox="0 0 24 24"><use href="#i-bolt"></use></svg></span>
        <span>Готово за минуту<small>студийное качество</small></span>
      </div>
    </div>
  </div>
</section>

<!-- ======================= ЛЕНТА ОБЛОЖЕК ======================= -->
@php
    use Illuminate\Support\Str;
    $cleanLabel = function ($t) {
        $label = trim((string) ($t['genre'] ?? ''));
        $label = trim((string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}]/u', '', $label));
        return Str::limit($label !== '' ? $label : 'Песня от ИИ', 30);
    };
    $marqueeTracks = collect($topTracks)->take(9);
@endphp
<section class="marquee-sec">
  <div class="marquee">
    <div class="marquee-track">
      @foreach([false, true] as $mqDup)
      @foreach($marqueeTracks as $t)
      <div class="mq"@if($mqDup) aria-hidden="true"@endif><div class="mq-cover"><img src="{{ $t['cover_url'] }}" alt="{{ $t['title'] }}" loading="lazy" width="120" height="120"><div class="mqp"><b>{{ Str::limit($t['title'], 30) }}</b><span>{{ $cleanLabel($t) }}</span></div></div></div>
      @endforeach
      @endforeach
    </div>
  </div>
</section>

<!-- ======================= 2. УТП ======================= -->
<section class="section" id="why">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Почему пользователи выбирают <span class="em">«На Репите»</span></h2>
    </div>
    <div class="utp-grid">
      <article class="card utp utp--key">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-mic"></use></svg></span>
        <h3>Чистый русский вокал без акцента</h3>
        <p>Правильное произношение, естественные интонации, точное попадание в ритм. Песню не отличить от студийной записи — без акцента, который выдаёт зарубежные сервисы.</p>
        <p class="spec-cap">Без акцента · студийное качество</p>
      </article>
      <article class="card utp utp--key">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-globe"></use></svg></span>
        <h3>Работает без VPN и регистрации</h3>
        <p>Сервис открывается из России напрямую — без обхода блокировок и иностранных карт. Первый трек создаётся сразу.</p>
        <p class="spec-cap">Из России · без регистрации</p>
      </article>
      <article class="card utp">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-bolt"></use></svg></span>
        <h3>Готовая песня за 1 минуту</h3>
        <p>Опишите идею или вставьте текст — ИИ напишет слова, подберёт мелодию, аранжировку и вокал.</p>
        <p class="spec-cap">≈ 60 сек · текст + вокал</p>
      </article>
      <article class="card utp">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-shield"></use></svg></span>
        <h3>Все права — ваши</h3>
        <p>Созданные треки принадлежат только вам. Используйте в роликах, рекламе и подарках, в том числе коммерчески.</p>
        <p class="spec-cap">100% ваши · коммерчески</p>
      </article>
    </div>
  </div>
</section>

<!-- ======================= 3. 3 ШАГА ======================= -->
<section class="section" id="how" style="background:var(--bg-2)">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Всего 3 шага для создания песни</h2>
    </div>
    <div class="steps">
      <article class="card step">
        <span class="step-n">01</span>
        <h3>Выберите стиль, жанр и голос</h3>
        <p>Более 1000 исполнителей, десятки жанров, мужской и женский вокал, разные тембры и настроение.</p>
        <span class="step-arrow"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </article>
      <article class="card step">
        <span class="step-n">02</span>
        <h3>Опишите идею или загрузите текст</h3>
        <p>ИИ создаст текст с рифмой и структурой — подходит и для коротких, и для полноценных треков.</p>
        <span class="step-arrow"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </article>
      <article class="card step">
        <span class="step-n">03</span>
        <h3>Скачайте готовый трек</h3>
        <p>Через минуту — студийное качество, чистый звук и живой вокал. Трек готов к использованию.</p>
      </article>
    </div>
    <div class="steps-cta">
      <span class="link-note">Идею описывать необязательно — ИИ подскажет.</span>
      <a href="/create-song" class="btn btn--primary btn--lg">
        <svg viewBox="0 0 24 24"><use href="#i-spark"></use></svg>Создать трек</a>
    </div>
  </div>
</section>

<!-- ======================= 4. ПРИМЕРЫ ТРЕКОВ ======================= -->
<section class="section" id="examples">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Послушайте, что создают на <span class="em">«На Репите»</span></h2>
      <p class="sub-sec">Случайные призёры наших чартов — одновременно играет только один трек.</p>
    </div>
    <div class="track-grid">
      @foreach(collect($topTracks)->take(6) as $t)
      <article class="card track" data-song-id="{{ $t['song_id'] }}" data-audio="{{ $t['audio_url'] }}">
        <div class="track-cover">
          <img src="{{ $t['cover_url'] }}" alt="{{ $t['title'] }}" loading="lazy">
          <span class="play-fab js-play"><svg viewBox="0 0 24 24"><use href="#i-play"></use></svg></span>
          <div class="track-stats">
            <span class="tstat"><svg viewBox="0 0 24 24"><use href="#i-play"></use></svg><span class="js-plays">{{ number_format((int) $t['plays'], 0, '', ' ') }}</span></span>
            <span class="tstat"><svg viewBox="0 0 24 24"><use href="#i-thumb"></use></svg>{{ $t['votes'] }}</span>
          </div>
        </div>
        <div class="track-body">
          <div class="track-title">{{ Str::limit($t['title'], 44) }}</div>
          <div class="track-author">{{ $t['author'] }}</div>
          <div class="mini-player">
            <span class="mp-btn js-play"><svg viewBox="0 0 24 24"><use href="#i-play"></use></svg></span>
            <div class="mp-progress">
              <div class="mp-bar js-bar"><i style="width:0%"></i></div>
              <div class="mp-time"><span class="js-cur">0:00</span><span class="js-dur">–:–</span></div>
            </div>
          </div>
          @php
              $promptLabel = trim((string) ($t['occasion'] ?? ''));
              $promptKind = 'Повод';
              if ($promptLabel === '') { $promptLabel = trim((string) ($t['genre'] ?? '')); $promptKind = 'Жанр'; }
              $promptLabel = trim((string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}]/u', '', $promptLabel));
          @endphp
          @if($promptLabel !== '')
          <div class="prompt-row">
            <span class="pic"><svg viewBox="0 0 24 24"><use href="#i-spark"></use></svg></span>
            <p><b>{{ $promptKind }}</b>{{ Str::limit($promptLabel, 70) }}</p>
          </div>
          @endif
        </div>
      </article>
      @endforeach
    </div>
  </div>
</section>

<!-- ======================= 5. ПЕСНЯ ПОД ПОВОД ======================= -->
<section class="section" id="povod" style="background:var(--bg-2)">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Песня под <span class="em">любой повод</span></h2>
      <p class="sub-sec">ИИ создаст персональную песню под ваше событие — с именами, деталями и настроением.</p>
    </div>
    <div class="povod-grid">
      <a class="povod" href="/pages/povod-dlya-pesni/den-rozhdeniya">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-gift"></use></svg></span>
        <span>Песня на день рождения</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
      <a class="povod" href="/pages/povod-dlya-pesni/svadba">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-rings"></use></svg></span>
        <span>Песня на свадьбу</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
      <a class="povod" href="/pages/povod-dlya-pesni/priznanie-v-lyubvi">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-heart"></use></svg></span>
        <span>Признание в любви</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
      <a class="povod" href="/pages/povod-dlya-pesni/yubiley">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-cal"></use></svg></span>
        <span>Песня на юбилей</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
      <a class="povod" href="/pages/povod-dlya-pesni/dlya-rebenka">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-balloon"></use></svg></span>
        <span>Песня для ребёнка</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
      <a class="povod" href="/pages/povod-dlya-pesni/den-materi">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-star"></use></svg></span>
        <span>Песня на День матери</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
      <a class="povod" href="/pages/povod-dlya-pesni/korporativ">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-snow"></use></svg></span>
        <span>Песня на корпоратив</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
      <a class="povod" href="/pages/povod-dlya-pesni/vypusknoy">
        <span class="pv-ic"><svg viewBox="0 0 24 24"><use href="#i-cap"></use></svg></span>
        <span>Песня на выпускной</span>
        <span class="arr"><svg viewBox="0 0 24 24"><use href="#i-arr"></use></svg></span>
      </a>
    </div>
    <div class="addressees">
      <span class="lbl">Песня в подарок:</span>
      <a class="chip chip--accent" href="/create-song">маме</a>
      <a class="chip chip--accent" href="/create-song">папе</a>
      <a class="chip chip--accent" href="/create-song">подруге</a>
      <a class="chip chip--accent" href="/pages/povod-dlya-pesni/dlya-druga">другу</a>
      <a class="chip chip--accent" href="/create-song">коллеге</a>
      <a class="chip chip--accent" href="/create-song">бабушке</a>
    </div>
  </div>
</section>

<!-- ======================= 6. ЖАНРЫ И ГОЛОСА ======================= -->
<section class="section" id="genres">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Любой жанр и голос на выбор</h2>
      <p class="sub-sec">Более 1000 исполнителей и десятки жанров. Мужской или женский вокал, тембр, настроение и подача.</p>
    </div>
    <div class="chip-cloud">
      <a class="chip" href="/create-song">Поп</a><a class="chip" href="/create-song">Рок</a><a class="chip" href="/create-song">Рэп</a>
      <a class="chip" href="/create-song">Шансон</a><a class="chip" href="/create-song">Хип-хоп</a><a class="chip" href="/create-song">Электроника</a>
      <a class="chip" href="/create-song">Фолк</a><a class="chip" href="/create-song">Джаз</a><a class="chip" href="/create-song">R&amp;B</a>
      <a class="chip" href="/create-song">Кантри</a><a class="chip" href="/create-song">Металл</a><a class="chip" href="/create-song">Лоу-фай</a>
      <a class="chip" href="/create-song">Классика</a><a class="chip" href="/create-song">EDM</a><a class="chip" href="/create-song">Регги</a>
      <a class="chip" href="/create-song">Военно-патриотическая</a><a class="chip" href="/create-song">Детская</a>
    </div>
  </div>
</section>

<!-- ======================= 7. ГДЕ ИСПОЛЬЗОВАТЬ ======================= -->
<section class="section" id="usecases" style="background:var(--bg-2)">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Где пригодятся песни от нейросети</h2>
    </div>
    <div class="use-grid">
      <article class="card use">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-gift"></use></svg></span>
        <h3>Подарки</h3>
        <p>Персональные песни на день рождения, свадьбу, годовщину.</p>
      </article>
      <article class="card use">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-users"></use></svg></span>
        <h3>Соцсети</h3>
        <p>Треки для YouTube, Shorts, Reels, TikTok — без проблем с авторскими правами.</p>
      </article>
      <article class="card use">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-brief"></use></svg></span>
        <h3>Бизнес</h3>
        <p>Джинглы, аудиологотипы, реклама и брендинг.</p>
      </article>
      <article class="card use">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-mega"></use></svg></span>
        <h3>Мероприятия</h3>
        <p>Гимны для корпоративов, выпускных и праздников.</p>
      </article>
      <article class="card use">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-palette"></use></svg></span>
        <h3>Творчество</h3>
        <p>Демо, эксперименты со стилями, идеи для треков.</p>
      </article>
      <article class="card use">
        <span class="ic-tile"><svg viewBox="0 0 24 24"><use href="#i-note"></use></svg></span>
        <h3>Контент-маркетинг</h3>
        <p>Вирусные ролики и музыкальные интеграции.</p>
      </article>
    </div>
  </div>
</section>

<!-- ======================= 10. ТАРИФЫ ======================= -->
<section class="section" id="pricing">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Выберите свой тариф</h2>
      <p class="sub-sec">Оплата разовая, без подписки и скрытых платежей. Песни зачисляются на баланс — создавайте, когда удобно. Разовая генерация на странице создания — 199&nbsp;₽.</p>
    </div>
    <div class="plans plans--3" id="plansRow">
      <article class="card plan">
        <h3>Хочу попробовать</h3>
        <div class="price">499&nbsp;₽<small> / 2 песни</small></div>
        <p class="note">Никогда не сталкивались с генерацией песен? Этот тариф для вас.</p>
        <ul>
          <li><svg viewBox="0 0 24 24"><use href="#i-check"></use></svg>2 песни на балансе</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-check"></use></svg>Студийный вокал без акцента</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-shield"></use></svg>Все права на треки — ваши</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-wallet"></use></svg>Оплата российскими картами</li>
        </ul>
        <a href="/tarify" class="btn btn--ghost btn--block">Выбрать тариф</a>
      </article>
      <article class="card plan plan--pro">
        <span class="plan-flag">Популярный</span>
        <h3>Для любителей</h3>
        <div class="price">999&nbsp;₽<small> / 7 песен</small></div>
        <p class="note">Музыкальные поздравления на несколько праздников и для нескольких близких.</p>
        <ul>
          <li><svg viewBox="0 0 24 24"><use href="#i-check"></use></svg>7 песен на балансе</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-check"></use></svg>≈143&nbsp;₽ за песню</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-shield"></use></svg>Все права на треки — ваши</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-wallet"></use></svg>Оплата российскими картами</li>
        </ul>
        <a href="/tarify" class="btn btn--primary btn--block">Выбрать тариф</a>
      </article>
      <article class="card plan">
        <h3>Для профессионалов</h3>
        <div class="price">2999&nbsp;₽<small> / 35 песен</small></div>
        <p class="note">Создаёте музыку на постоянной основе — самая низкая цена за генерацию.</p>
        <ul>
          <li><svg viewBox="0 0 24 24"><use href="#i-check"></use></svg>35 песен на балансе</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-check"></use></svg>≈86&nbsp;₽ за песню — выгоднее всего</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-shield"></use></svg>Все права на треки — ваши</li>
          <li><svg viewBox="0 0 24 24"><use href="#i-wallet"></use></svg>Оплата российскими картами</li>
        </ul>
        <a href="/tarify" class="btn btn--ghost btn--block">Выбрать тариф</a>
      </article>
    </div>
    <div class="plans-dots" id="plansDots" aria-hidden="true"><i class="on"></i><i></i><i></i></div>
  </div>
</section>

<!-- ======================= 11. FAQ ======================= -->
<section class="section" id="faq">
  <div class="wrap">
    <div class="sec-head center">
      <h2 class="h-sec">Частые вопросы</h2>
    </div>
    <div class="faq">
      <details class="faq-item" open="">
        <summary class="faq-q"><h3>Сколько стоит создать песню?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Одна готовая песня стоит 199 ₽ — оплата разовая, за конкретный трек. Никакой подписки: вы платите только за те песни, которые создаёте.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>Нужен ли VPN, чтобы пользоваться сервисом?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Нет. «На Репите» работает напрямую из России — без VPN, обхода блокировок и иностранных карт. Оплата — российскими картами.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>Кому принадлежат права на песню? Можно ли использовать коммерчески и на YouTube?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Все права принадлежат вам. Песню можно использовать в личных и коммерческих проектах: в роликах, рекламе, на YouTube и в соцсетях.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>На каком языке поёт нейросеть?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Основной упор — русский: чистый вокал без акцента, правильное произношение и ударения. Поддерживаются и другие языки.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>Нужно ли уметь писать музыку или тексты?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Нет. Достаточно описать идею, повод или настроение — ИИ сам напишет текст с рифмой, подберёт мелодию, аранжировку и вокал.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>Сколько длится генерация и в каком формате трек?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Примерно 1 минута. Трек скачивается в высоком качестве и сразу готов к использованию.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>Что делать, если в песне неправильное ударение?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Помогает замена «е» на «ё» в нужном слове или замена синонимом. Текст лучше делить на блоки, кратные 4 строкам, — так вокал ложится ровнее.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>Можно ли вставить свой готовый текст?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Да. Вы загружаете свои слова — нейросеть споёт их или доработает под жанр и ритм.</div>
      </details>
      <details class="faq-item">
        <summary class="faq-q"><h3>Чем вы отличаетесь от Suno?</h3><span class="faq-ic"><svg viewBox="0 0 24 24"><use href="#i-chev"></use></svg></span></summary>
        <div class="faq-a">Suno и аналоги заточены под английский и часто требуют VPN. «На Репите» сделан под русский: чистый вокал без акцента, русский интерфейс, доступ без VPN и понятная оплата — 199 ₽ за песню.</div>
      </details>
    </div>
  </div>
</section>

<!-- ======================= 12. ФИНАЛЬНЫЙ CTA ======================= -->
<section class="section section--tight">
  <div class="wrap">
    <div class="final reveal">
      <div class="final-wave" data-wave="38"></div>
      <h2>Создай свою песню прямо сейчас</h2>
      <p>Готовая песня за минуту. Без VPN и регистрации — оплата 199 ₽ за трек.</p>
      <a href="/create-song" class="btn btn--primary btn--lg">
        <svg viewBox="0 0 24 24"><use href="#i-spark"></use></svg>Создать трек</a>
    </div>
  </div>
</section>

<!-- ======================= 13. SEO-ТЕКСТ ======================= -->
<section class="section section--tight">
  <div class="wrap">
    <div class="seo">
      <h2>Создание песен нейросетью онлайн</h2>
      <p><b>«На Репите»</b> — это нейросеть для генерации песен, которая превращает идею в готовый музыкальный трек с живым вокалом. Чтобы создать песню онлайн, не нужно владеть нотной грамотой, арендовать студию или устанавливать программы: достаточно описать тему, настроение или вставить готовый текст — а ИИ напишет слова, подберёт мелодию и споёт.</p>
      <p>Главная особенность сервиса — <b>чистый русский вокал без акцента</b>. Большинство зарубежных генераторов музыки заточены под английский, и русские песни в них звучат с искажениями и ошибками в ударениях. Мы построили «На Репите» вокруг русского языка: правильное произношение, естественные интонации и точное попадание в ритм. При этом сервис работает напрямую из России — без VPN, обхода блокировок и иностранных карт.</p>
      <p>С помощью генератора музыки можно написать текст песни нейросетью с нуля или доработать свой, выбрать жанр — поп, рок, рэп, шансон, электронику, фолк и десятки других — и задать мужской или женский вокал. Готовый трек создаётся примерно за минуту и скачивается в высоком качестве. Все права на песню принадлежат вам, поэтому её можно использовать в роликах для YouTube, Reels и TikTok, в рекламе и брендинге, а также как оригинальный подарок.</p>
      <p>Чаще всего нейросеть используют, чтобы сделать песню в подарок — на день рождения, свадьбу, годовщину или признание в любви. Достаточно указать имена, важные события и личные детали, и ИИ создаст персональную композицию, которую невозможно купить готовой. Создать песню можно за 199 ₽ — оплата разовая, за конкретный трек, и ваша песня окажется у всех «на репите».</p>
    </div>
  </div>
</section>

<!-- ======================= FOOTER ======================= -->
<footer class="ftr">
  <div class="wrap">
    <div class="ftr-grid">
      <div>
        <a href="/" class="logo">
          <img src="/static/landing2/logo.svg" alt="На Репите" class="logo-img">
        </a>
        <p class="ftr-about">Нейросеть для создания песен с живым русским вокалом — без акцента, без VPN и без регистрации.</p>
      </div>
      <div>
        <h4>Навигация</h4>
        <ul>
          <li><a href="/articles">Статьи</a></li>
          <li><a href="/pages/povod-dlya-pesni">Повод для песни</a></li>
          <li><a href="/tarify">Тарифы</a></li>
          <li><a href="/login">Вход</a></li>
          <li><a href="/register">Регистрация</a></li>
          <li><a href="/oferta">Оферта</a></li>
          <li><a href="/privacy">Политика конфиденциальности</a></li>
        </ul>
      </div>
      <div>
        <h4>Мессенджеры</h4>
        <ul>
          <li><a href="https://t.me/na_repitebot">Telegram-бот</a></li>
          <li><a href="https://max.ru/id501216944367_bot">MAX-бот</a></li>
        </ul>
      </div>
    </div>
    <div class="ftr-bot">
      <span>© 2026 На Репите. Все права защищены.</span>
      <span>Создайте свой трек — и он окажется у всех «на репите».</span>
    </div>
  </div>
</footer>

<script>
// Декоративные аудио-волны
(function(){
  document.querySelectorAll('[data-wave]').forEach(function(el){
    var n = +el.getAttribute('data-wave') || 48;
    var max = el.classList.contains('final-wave') ? 38 : 30;
    var frag = document.createDocumentFragment();
    for(var i=0;i<n;i++){
      var b = document.createElement('i');
      var t = i/n;
      var h = (Math.sin(t*Math.PI*6)*0.5+0.5)*(max-7)+7;
      h *= 0.55 + Math.random()*0.5;
      b.style.height = Math.round(h)+'px';
      frag.appendChild(b);
    }
    el.appendChild(frag);
  });
})();

// Mobile drawer
(function(){
  var burger=document.getElementById('mobBurger');
  var drawer=document.getElementById('mobDrawer');
  var overlay=document.getElementById('mobOverlay');
  var close=document.getElementById('mobClose');
  function open(){drawer.classList.add('is-open');overlay.classList.add('is-open');document.body.style.overflow='hidden';drawer.setAttribute('aria-hidden','false');}
  function shut(){drawer.classList.remove('is-open');overlay.classList.remove('is-open');document.body.style.overflow='';drawer.setAttribute('aria-hidden','true');}
  if(burger)burger.addEventListener('click',open);
  if(close)close.addEventListener('click',shut);
  if(overlay)overlay.addEventListener('click',shut);
  document.addEventListener('keydown',function(e){if(e.key==='Escape')shut();});
  drawer.querySelectorAll('a').forEach(function(a){a.addEventListener('click',shut);});
})();
</script>

<style>
/* ============================================================
   MOBILE DRAWER + FULL RESPONSIVE
   ============================================================ */

/* --- burger button --- */
.mob-burger{
  display:none;flex-direction:column;justify-content:center;gap:5px;
  width:44px;height:44px;background:transparent;border:none;cursor:pointer;padding:10px;
  border-radius:10px;flex:0 0 auto;margin-right:4px;
}
.mob-burger span{display:block;width:22px;height:2px;background:#fff;border-radius:2px;transition:.2s}
.mob-burger:hover span{background:rgba(255,255,255,.75)}

/* --- overlay --- */
.mob-overlay{
  display:none;position:fixed;inset:0;z-index:100;
  background:rgba(8,6,18,.55);backdrop-filter:blur(4px);opacity:0;transition:opacity .28s;
}
.mob-overlay.is-open{opacity:1}

/* --- drawer --- */
.mob-drawer{
  position:fixed;top:0;right:0;bottom:0;z-index:101;
  width:min(82vw,320px);
  background:linear-gradient(175deg,#0E1330 0%,#1A1640 55%,#2A1840 100%);
  transform:translateX(100%);transition:transform .32s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;padding:24px 0 40px;
  box-shadow:-8px 0 40px rgba(0,0,0,.5);
}
.mob-drawer.is-open{transform:translateX(0)}
.mob-overlay,.mob-drawer{pointer-events:none}
.mob-overlay.is-open,.mob-drawer.is-open{pointer-events:auto}

/* close button */
.mob-close{
  align-self:flex-end;margin-right:20px;margin-bottom:20px;
  width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.14);display:grid;place-items:center;cursor:pointer;
  color:#fff;transition:.18s;flex:0 0 auto;
}
.mob-close:hover{background:rgba(255,255,255,.16)}
.mob-close svg{width:18px;height:18px}

/* nav list */
.mob-nav{list-style:none;padding:0;margin:0;overflow-y:auto;}
.mob-nav li{border-bottom:1px solid rgba(255,255,255,.1)}
.mob-nav li:first-child{border-top:1px solid rgba(255,255,255,.1)}
.mob-nav a{
  display:block;padding:20px 28px;
  font-family:'Onest','Golos Text',system-ui,sans-serif;
  font-size:20px;font-weight:400;color:#fff;letter-spacing:-.01em;
  transition:background .16s,padding-left .16s;
}
.mob-nav a:hover{background:rgba(255,255,255,.07);padding-left:36px}

/* show burger at mobile */
@media (max-width:1080px){
  .mob-burger{display:flex}
}

/* ============================================================
   FULL MOBILE LAYOUT (≤ 767px)
   ============================================================ */
@media (max-width:767px){

  /* layout */
  .wrap{padding:0 18px}
  .section{padding:56px 0}
  .section--tight{padding:40px 0}
  .sec-head{margin-bottom:32px}

  /* type */
  .h-sec{font-size:30px;max-width:none}
  .sub-sec{font-size:16px}
  .hero h1{font-size:30px;letter-spacing:-.02em}
  .hero-sub{font-size:16px;margin-top:16px}

  /* header */
  .hdr-in{height:62px;gap:10px}
  .hdr-cta{display:none}
  .nav{display:none}
  .logo-img{height:28px}

  /* hero */
  .hero{padding:36px 0 48px}
  .hero-grid{grid-template-columns:1fr;gap:32px}
  .hero-copy{max-width:none}
  .hero-cta{flex-direction:column;gap:10px}
  .hero-cta .btn{width:100%;justify-content:center}
  .trust{font-size:12.5px;gap:7px}
  .hero-art{max-width:340px;margin:0 auto}
  .float-chip{display:none}

  /* marquee */
  .mq{width:120px}
  .mq-cover{width:120px;height:120px}
  .marquee-sec{padding:16px 0 0}

  /* УТП */
  .utp-grid{grid-template-columns:1fr;gap:14px}
  .utp{padding:22px 20px 24px}
  .utp h3{font-size:18px}

  /* 3 шага */
  .steps{grid-template-columns:1fr;gap:0}
  .step{padding:24px 22px 26px;border-radius:0}
  .step:first-child{border-radius:var(--r-lg) var(--r-lg) 0 0}
  .step:last-child{border-radius:0 0 var(--r-lg) var(--r-lg)}
  .step+.step{border-top:1px solid var(--border)}
  .step-arrow{display:none}
  .steps-cta{flex-direction:column;align-items:center;gap:14px;text-align:center}
  .steps-cta .btn{width:100%}

  /* треки */
  .track-grid{grid-template-columns:1fr;gap:18px}
  .track-title{font-size:16px}

  /* повод */
  .povod-grid{grid-template-columns:1fr 1fr;gap:12px}
  .povod{padding:14px 14px;gap:10px}
  .povod span:not(.pv-ic):not(.arr){font-size:13.5px}
  .pv-ic{width:36px;height:36px}
  .pv-ic svg{width:18px;height:18px}

  /* адресаты */
  .addressees{gap:8px}
  .chip{padding:8px 14px;font-size:14px}

  /* жанры */
  .chip-cloud{gap:8px}

  /* где использовать */
  .use-grid{grid-template-columns:1fr;gap:14px}
  .use{padding:22px 20px 24px}

  /* отзывы + метрики */
  .metrics{grid-template-columns:1fr;gap:14px}
  .metric .num{font-size:40px}
  .rev-grid{grid-template-columns:1fr;gap:14px}

  /* тарифы */
  .plans{grid-template-columns:1fr;max-width:none}
  .plan{padding:28px 24px}
  .plan .price{font-size:32px}

  /* FAQ */
  .faq-q h3{font-size:16px}
  .faq-a{padding:0 18px 22px 60px;font-size:15px}

  /* финальный CTA */
  .final{padding:56px 24px;border-radius:var(--r-lg)}
  .final h2{font-size:30px}
  .final p{font-size:16px}
  .final .btn{width:100%}

  /* SEO */
  .seo h2{font-size:22px}
  .seo p{font-size:15px}

  /* footer */
  .ftr-grid{grid-template-columns:1fr;gap:28px}
  .ftr-bot{flex-direction:column;gap:8px;font-size:13px}
}

/* ============================================================
   TABLET (768 – 1079px)
   ============================================================ */
@media (min-width:768px) and (max-width:1079px){
  .wrap{padding:0 24px}
  .section{padding:72px 0}
  .h-sec{font-size:38px}
  .hero h1{font-size:44px}
  .hero-grid{grid-template-columns:1fr;gap:40px}
  .hero-cta{flex-wrap:wrap}
  .hero-art{max-width:420px;margin:0 auto}
  .float-chip{display:none}
  .utp-grid{grid-template-columns:repeat(2,1fr)}
  .track-grid{grid-template-columns:repeat(2,1fr)}
  .steps{grid-template-columns:1fr}
  .step-arrow{display:none}
  .povod-grid{grid-template-columns:repeat(2,1fr)}
  .use-grid{grid-template-columns:repeat(2,1fr)}
  .plans{grid-template-columns:1fr;max-width:480px}
  .ftr-grid{grid-template-columns:1fr 1fr}
  .rev-grid{grid-template-columns:1fr}
}
</style>

<style>
/* ============================================================
   ТАРИФЫ: 3 в ряд на десктопе, слайдер с точками на мобиле
   ============================================================ */
.plans--3{grid-template-columns:repeat(3,1fr);max-width:1120px}
.plans-dots{display:none}
@media (min-width:768px) and (max-width:1079px){
  .plans--3{grid-template-columns:1fr;max-width:480px}
}
@media (max-width:767px){
  .plans--3{
    display:flex;grid-template-columns:none;max-width:none;
    overflow-x:auto;scroll-snap-type:x mandatory;gap:14px;
    padding:4px 2px 8px;-webkit-overflow-scrolling:touch;
    scrollbar-width:none;
  }
  .plans--3::-webkit-scrollbar{display:none}
  .plans--3 .plan{flex:0 0 100%;scroll-snap-align:center;scroll-snap-stop:always}
  .plans-dots{display:flex;justify-content:center;gap:9px;margin-top:18px}
  .plans-dots i{width:8px;height:8px;border-radius:50%;background:var(--border-hi);transition:.2s}
  .plans-dots i.on{background:var(--violet);transform:scale(1.3)}
}
</style>

<script>
// ============================================================
// Реальный аудиоплеер: один трек одновременно + счётчик прослушиваний
// ============================================================
(function(){
  var current = null;
  function fmt(sec){
    if (!isFinite(sec)) return '–:–';
    sec = Math.floor(sec);
    return Math.floor(sec/60) + ':' + ('0' + (sec % 60)).slice(-2);
  }
  document.querySelectorAll('.track[data-audio]').forEach(function(card){
    var audio = null, counted = false;
    var bar = card.querySelector('.js-bar');
    var cur = card.querySelector('.js-cur');
    var dur = card.querySelector('.js-dur');
    var mpBtn = card.querySelector('.mp-btn');

    function setIcons(playing){
      card.querySelectorAll('.js-play').forEach(function(b){
        b.innerHTML = '<svg viewBox="0 0 24 24"><use href="#i-' + (playing ? 'pause' : 'play') + '"></use></svg>';
      });
      card.classList.toggle('track--playing', playing);
      if (mpBtn) {
        mpBtn.style.background = playing ? 'var(--grad)' : '';
        mpBtn.style.borderColor = playing ? 'transparent' : '';
        mpBtn.style.color = playing ? '#fff' : '';
      }
    }
    function stop(){ if (audio) audio.pause(); setIcons(false); }
    function ensure(){
      if (audio) return audio;
      audio = new Audio(card.dataset.audio);
      audio.preload = 'none';
      audio.addEventListener('loadedmetadata', function(){ if (dur) dur.textContent = fmt(audio.duration); });
      audio.addEventListener('timeupdate', function(){
        if (cur) cur.textContent = fmt(audio.currentTime);
        if (bar && audio.duration) bar.firstElementChild.style.width = (audio.currentTime / audio.duration * 100) + '%';
      });
      audio.addEventListener('ended', function(){ setIcons(false); current = null; });
      return audio;
    }
    function toggle(){
      var a = ensure();
      if (current && current.audio !== a) current.stop();
      if (a.paused) {
        a.play();
        setIcons(true);
        current = { audio: a, stop: stop };
        if (!counted) {
          counted = true;
          fetch('/api/landing/play', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ song_id: +card.dataset.songId })
          }).then(function(r){ return r.json(); }).then(function(d){
            var el = card.querySelector('.js-plays');
            if (el && d && d.plays_count != null) el.textContent = d.plays_count.toLocaleString('ru-RU');
          }).catch(function(){});
        }
      } else {
        a.pause();
        setIcons(false);
        current = null;
      }
    }
    card.querySelectorAll('.js-play').forEach(function(b){ b.addEventListener('click', toggle); });
    if (bar) bar.parentElement.addEventListener('click', function(e){
      if (e.target.closest('.js-play')) return;
      if (!audio || !audio.duration) return;
      var r = bar.getBoundingClientRect();
      audio.currentTime = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)) * audio.duration;
    });
  });
})();

// ============================================================
// Точки пагинации слайдера тарифов (мобайл)
// ============================================================
(function(){
  var row = document.getElementById('plansRow');
  var dotsWrap = document.getElementById('plansDots');
  if (!row || !dotsWrap) return;
  var dots = dotsWrap.querySelectorAll('i');
  function update(){
    var w = row.clientWidth;
    if (!w) return;
    var i = Math.round(row.scrollLeft / (w + 14));
    i = Math.max(0, Math.min(dots.length - 1, i));
    dots.forEach(function(d, k){ d.classList.toggle('on', k === i); });
  }
  row.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  update();
})();
</script>

</body></html>