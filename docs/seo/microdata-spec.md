# Микроразметка для narepite.com

Полное ТЗ по типам микроразметки на всех страницах.

## Таблица типов разметки

Страница / Раздел	Тип микроразметки	Ключевые свойства (все поля из ТЗ)
Все страницы (кроме главной, /articles и /create-song)	BreadcrumbList	"itemListElement, item (URL), name (Text), position (Integer). 

Последний элемент без ссылки, position от 1, абсолютные URL."
Все страницы	SiteNavigationElement	"itemtype, itemprop=""url"", itemprop=""name"", aria-current, aria-label. 

Только абсолютные URL, один блок на страницу."
/articles (общий список статей)	ItemList + ListItem + BlogPosting	"ItemList: itemListElement, itemListOrder, numberOfItems

ListItem: position, item

BlogPosting: headline, url, image, datePublished, description"
Статьи (обычные, ~12 URL)	BlogPosting	headline, url, image, datePublished, dateModified, description, articleBody, publisher (вложенные: name, url, logo→url/width/height), mainEntityOfPage
Статьи (пошаговые гайды, ~15 URL)	BlogPosting + HowTo	"Все поля BlogPosting выше +

HowTo: name, description, totalTime (ISO 8601), step (HowToStep), tool (HowToTool)

HowToStep: name, text, image"
Конец каждой статьи	ItemList (блок «Читайте также»)	itemListElement, position, item → headline, url, image, datePublished
/pages/povod-dlya-pesni/ и вложенные	Service	name, description, serviceType, provider (name, url, logo), areaServed, offers (priceCurrency, price, availability, url), hasOfferCatalog, url, image, availableLanguage
/pages/povod-dlya-pesni/ и вложенные	ItemList + MusicComposition (примеры песен)	"ItemList: itemListElement, itemListOrder, numberOfItems, position

MusicComposition: name, url, creator (name), genre, lyrics, audio, image, inLanguage"
/pages/povod-dlya-pesni/ и вложенные	FAQPage	mainEntity (Question), name (текст вопроса), acceptedAnswer (Answer), text (текст ответа)
/create-song	WebApplication	name, description, applicationCategory, operatingSystem, offers (priceCurrency, price, priceValidUntil, availability, url), featureList, url, provider (Organization), inLanguage
/create-song	ItemList + MusicComposition (Лучшие песни)	Те же свойства, что для раздела «Поводы»
/ (Главная)	WebSite	name, url, description, publisher (ссылка на Organization по ID)
/ (Главная, footer/скрытый)	Organization	name, url, logo (url, width, height), sameAs, contactPoint (contactType, availableLanguage, email), email
/ (Главная, main-контент)	WebApplication	Те же свойства, что для /create-song + provider (ссылка на Organization по ID)
/ (Главная)	ItemList + MusicComposition (Лучшие песни)	Те же свойства, что для раздела «Поводы»
/help	WebPage + ContactPoint	"WebPage: name, description, url

ContactPoint: contactType, availableLanguage, email"
/tarify	Service + OfferCatalog + Offer + PriceSpecification	"Service: name, serviceType, provider, areaServed, hasOfferCatalog

OfferCatalog: name, itemListElement

ListItem: position, item

Offer: name, description, priceSpecification, availability, url

PriceSpecification: priceCurrency, price, valueReference"

## Прогресс реализации

- [x] Organization + WebSite на главной
- [x] WebApplication на главной и /create-song
- [x] BreadcrumbList глобально
- [x] SiteNavigationElement глобально
- [x] Service + Offer на /tarify
- [x] WebPage + ContactPoint на /help
- [x] BlogPosting для обычных статей
- [ ] BlogPosting + HowTo для гайдов
- [ ] ItemList + BlogPosting на /articles + "Читайте также"
- [ ] Service + MusicComposition + FAQPage для /pages/povod-dlya-pesni/
- [x] ItemList + MusicComposition "Лучшие песни"

## Общие правила

- Формат разметки: JSON-LD в `<script type="application/ld+json">` в `<head>` или в конце `<body>`
- Все URL — абсолютные (`https://narepite.com/...`)
- Использовать `@id` для перекрёстных ссылок между сущностями (Organization → WebApplication etc)
- Валидация:
  - https://search.google.com/test/rich-results
  - https://validator.schema.org/
- Не дублировать одну сущность на странице
