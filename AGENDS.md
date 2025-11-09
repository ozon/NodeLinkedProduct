# Node LinkedProduct – Projektagenda
Shopware 6.6 / 6.7 Plugin – Anzeige eines verknüpften Produkts auf PDP & in Erlebniswelten

---

## Ziel
Plugin zur Anzeige eines zusätzlichen Produkts in der Produktdetailseite (PDP) und in Erlebniswelten (CMS) über Produkt-ID.  
Die Produkt-ID wird entweder im Produkt über ein Custom Field oder in der CMS-Element-Konfiguration definiert.

---

## Technische Basis
- **Vendor:** Node  
- **Plugin:** LinkedProduct  
- **Namespace:** `Node\LinkedProduct`  
- **Composer Name:** `node/linked-product`  
- **Shopware Version:** ≥ 6.6.0 < 6.8.0  
- **PHP Version:** ≥ 8.2  
- **Pfad:** `/custom/plugins/NodeLinkedProduct/`

---

## Projektstruktur

```
NodeLinkedProduct/
├── composer.json
├── NodeLinkedProduct.php
├── Resources/
│   ├── config/services.xml
│   ├── app/
│   │   ├── administration/src/
│   │   │   ├── main.js
│   │   │   └── module/sw-cms/elements/node-linked-product/
│   │   │       ├── index.js
│   │   │       ├── component/
│   │   │       │   ├── index.js
│   │   │       │   ├── sw-cms-el-node-linked-product.html.twig
│   │   │       │   └── sw-cms-el-node-linked-product.scss
│   │   │       ├── config/
│   │   │       │   ├── index.js
│   │   │       │   └── sw-cms-el-config-node-linked-product.html.twig
│   │   │       └── snippet/
│   │   │           ├── de-DE.json
│   │   │           └── en-GB.json
│   │   └── storefront/src/
│   │       ├── main.js
│   │       └── scss/linked-product.scss
│   ├── snippet/
│   │   ├── de-DE/node.linked_product.json
│   │   └── en-GB/node.linked_product.json
│   ├── theme.json
│   └── views/storefront/
│       ├── component/node-linked-product/teaser.html.twig
│       └── page/product-detail/buy-widget.html.twig
└── src/
    ├── Core/Service/LinkedProductLoader.php
    ├── Cms/Element/LinkedProductCmsElement.php
    ├── Cms/Element/LinkedProductCmsResolver.php
    ├── Storefront/Subscriber/ProductPageSubscriber.php
    └── Migration/Migration202511090001LinkedProductCustomField.php
```

---

## Gesamtübersicht Milestones

| Nr | Titel | Ziel |
|----|-------|------|
| **1** | Plugin-Grundgerüst & Custom-Field | Funktionsfähiges Plugin + Migration für `node_linked_product_id` |
| **2** | Loader-Service | Service zum Laden eines Produkts im SalesChannel-Kontext |
| **3** | PDP-Integration | Anzeige des verknüpften Produkts auf der Produktdetailseite |
| **4** | CMS-Element | Erlebniswelten-Element „Linked Product" mit Produktauswahl |
| **5** | Twig-Partial & Styles | Einheitliches Design für PDP & CMS |
| **6** | Qualität, Tests & Dokumentation | Snippets, Cache-Tags, Unit-Tests & README |

---

## Milestone 1 – Plugin-Grundgerüst & Custom-Field

**Objective:**  
Funktionsfähiges Shopware-Plugin mit Migration für Produkt-Custom-Field `node_linked_product_id`.

**Technical Context:**  
PSR-4, Composer, Doctrine DBAL MigrationStep, UUID-Handling via `Uuid::randomBytes()`.

**Requirements:**

1. **Plugin-Ordner:** `/custom/plugins/NodeLinkedProduct/`
2. **Namespace:** `Node\LinkedProduct`

3. **Dateien erstellen:**
   - `composer.json` mit:
     - Name: `node/linked-product`
     - Type: `shopware-platform-plugin`
     - PHP: `>=8.2`
     - Shopware: `>=6.6.0 <6.8.0`
     - PSR-4 Autoload für `Node\LinkedProduct\`
     - Plugin-Klasse: `Node\LinkedProduct\NodeLinkedProduct`
     - Labels DE/EN

   - `NodeLinkedProduct.php`:
     - Extends `Shopware\Core\Framework\Plugin`
     - `uninstall()` Methode löscht Custom Field + Set via SQL DELETE wenn `keepUserData()` false
     - Verwendet `Doctrine\DBAL\Connection`

   - `Resources/config/services.xml`:
     - Leere Service-Container-Definition
     - Wird in späteren Milestones erweitert

   - `src/Migration/Migration202511090001LinkedProductCustomField.php`:
     - Extends `Shopware\Core\Framework\Migration\MigrationStep`
     - `getCreationTimestamp()`: `1731158400`
     - `update()` erstellt:
       - Custom Field Set mit `Uuid::randomBytes()`:
         - Name: `node_linked_product`
         - Config mit Labels DE/EN: "Verknüpftes Produkt" / "Linked Product"
         - active: 1
         - created_at: aktuelles Datum im Format `Defaults::STORAGE_DATE_TIME_FORMAT`
       - Custom Field Set Relation:
         - Verknüpfung zu Entity `product`
       - Custom Field mit `Uuid::randomBytes()`:
         - Name: `node_linked_product_id`
         - Type: `entity` (NICHT `text`)
         - Config:
           - `entity`: `product`
           - `componentName`: `sw-entity-single-select`
           - Labels DE/EN
           - `customFieldPosition`: 1
         - Verknüpfung zu Custom Field Set

4. **Custom Field Spezifikation:**
   - Type MUSS `entity` sein für Admin Entity-Select
   - Entity MUSS `product` sein
   - ComponentName MUSS `sw-entity-single-select` sein

5. **Deinstallationslogik:**
   - SQL DELETE für `custom_field` WHERE `name = 'node_linked_product_id'`
   - SQL DELETE für `custom_field_set` WHERE `name = 'node_linked_product'`
   - Nur ausführen wenn `$uninstallContext->keepUserData()` false

**Instructions for LLM:**

- Erzeuge alle 4 Dateien mit vollständigem Code
- Custom Field MUSS Type `entity` haben (nicht `text`)
- Migration MUSS `Uuid::randomBytes()` für IDs verwenden
- Deinstallation MUSS beide SQL-Deletes enthalten
- `created_at` MUSS `Defaults::STORAGE_DATE_TIME_FORMAT` verwenden

**Test nach Implementierung:**
```bash
bin/console plugin:refresh
bin/console plugin:install -a NodeLinkedProduct
bin/console cache:clear
```

Admin prüfen → Custom Field bei Produkten sichtbar als Entity-Select-Dropdown.

---

## Milestone 2 – Loader-Service

**Objective:**  
Einheitlicher Service zum Laden eines Produkts anhand einer ID im SalesChannel-Kontext mit korrekter Visibility-Prüfung.

**Technical Context:**  
`SalesChannelRepository`, `SalesChannelProductEntity`, Criteria mit Associations und Filtern.

**Requirements:**

1. **Klasse erstellen:** `src/Core/Service/LinkedProductLoader.php`

2. **Service-Eigenschaften:**
   - Namespace: `Node\LinkedProduct\Core\Service`
   - Constructor Injection: `SalesChannelRepository $productRepository` (Service-ID: `sales_channel.product.repository`)

3. **Methode:** `loadById(string $productId, SalesChannelContext $context): ?SalesChannelProductEntity`

4. **Methoden-Logik:**
   - Erstelle `Criteria` mit `[$productId]`
   - Füge Associations hinzu:
     - `cover.media` (für Produktbild)
     - `manufacturer` (für Herstellername)
     - `seoUrls` (für SEO-URL-Generierung)
   - Füge Filter hinzu:
     - `EqualsFilter('active', true)` (nur aktive Produkte)
     - `EqualsFilter('visibilities.salesChannelId', $context->getSalesChannelId())` (nur im aktuellen SalesChannel sichtbare Produkte)
   - Führe `$productRepository->search($criteria, $context)` aus
   - Gib `$result->first()` zurück (Type: `?SalesChannelProductEntity`)

5. **Return-Verhalten:**
   - `SalesChannelProductEntity` bei Erfolg
   - `null` wenn Produkt nicht existiert, inaktiv oder nicht im SalesChannel sichtbar

6. **Service-Registrierung:**
   - In `Resources/config/services.xml` registrieren
   - Service-ID: `Node\LinkedProduct\Core\Service\LinkedProductLoader`
   - Argument: `sales_channel.product.repository`

**Instructions for LLM:**

- Erzeuge `LinkedProductLoader.php` mit komplettem Code
- Return Type MUSS `?SalesChannelProductEntity` sein (NICHT `?ProductEntity`)
- Repository MUSS `SalesChannelRepository` Type-Hint haben
- Visibility-Filter MUSS SalesChannelId prüfen
- Alle 3 Associations MÜSSEN vorhanden sein
- Beide Filter MÜSSEN gesetzt werden
- Registriere Service in `services.xml` mit korrekter Service-ID

**Test-Hinweis:**
- Unit-Test MUSS `EntitySearchResult` mit `SalesChannelProductEntity` mocken
- Mock MUSS `first()` Methode bereitstellen

---

## Milestone 3 – PDP-Integration unter Buybox

**Objective:**  
Zeigt das Zusatzprodukt auf der Produktdetailseite unterhalb des „In den Warenkorb"-Bereichs mit korrekten Cache-Tags an.

**Technical Context:**  
`ProductPageLoadedEvent`, Twig `sw_extends`, Cache-Tags Format `product.{id}`.

**Requirements:**

1. **Event Subscriber erstellen:** `src/Storefront/Subscriber/ProductPageSubscriber.php`

2. **Subscriber-Eigenschaften:**
   - Namespace: `Node\LinkedProduct\Storefront\Subscriber`
   - Implements: `EventSubscriberInterface`
   - Constructor Injection: `LinkedProductLoader $linkedProductLoader`

3. **Event-Registrierung:**
   - `getSubscribedEvents()` gibt zurück: `[ProductPageLoadedEvent::class => 'onProductPageLoaded']`

4. **Event-Handler-Logik:**
   - Hole Produkt via `$event->getPage()->getProduct()`
   - Hole Custom Fields via `$product->getCustomFields()`
   - Prüfe ob `$customFields['node_linked_product_id']` gesetzt ist
   - Wenn leer: `return` (nichts tun)
   - Lade verknüpftes Produkt via `$linkedProductLoader->loadById()`
   - Wenn `null`: `return` (nichts tun)
   - Füge Produkt als Page Extension hinzu: `$event->getPage()->addExtension('nodeLinkedProduct', $linkedProduct)`
   - Setze Cache-Tags:
     - Hole Response via `$event->getResponse()`
     - Prüfe ob Response instanceof `Response`
     - Hole bestehende Cache-Tags via `$response->headers->get('cache-tags', '[]')`
     - Dekodiere JSON
     - Füge Tag hinzu: `product.{linkedProductId}` (Format mit Punkt, NICHT Bindestrich)
     - Setze Header: `cache-tags` mit `json_encode(array_unique($cacheTags))`

5. **Template erstellen:** `Resources/views/storefront/page/product-detail/buy-widget.html.twig`

6. **Template-Logik:**
   - Verwende `{% sw_extends '@Storefront/storefront/page/product-detail/buy-widget.html.twig' %}` (NICHT `extend`)
   - Überschreibe Block: `page_product_detail_buy_inner`
   - Rufe Parent-Block auf: `{{ parent() }}`
   - Prüfe ob Extension existiert: `{% if page.extensions.nodeLinkedProduct %}`
   - Include Teaser: `{% sw_include '@NodeLinkedProduct/storefront/component/node-linked-product/teaser.html.twig' with { product: page.extensions.nodeLinkedProduct } %}`

7. **Service-Registrierung:**
   - In `services.xml` registrieren
   - Service-ID: `Node\LinkedProduct\Storefront\Subscriber\ProductPageSubscriber`
   - Tag: `kernel.event_subscriber`
   - Argument: `Node\LinkedProduct\Core\Service\LinkedProductLoader`

**Instructions for LLM:**

- Erzeuge Subscriber mit komplettem Event-Handling
- Cache-Tag-Format MUSS `product.{id}` sein (mit Punkt)
- Template MUSS `sw_extends` verwenden (NICHT `extend`)
- Include MUSS `sw_include` verwenden
- Extension-Key MUSS `nodeLinkedProduct` sein
- Fallback-Verhalten: Rendert nichts bei fehlender/ungültiger ID
- Registriere als Event Subscriber in `services.xml`

**Fallback-Verhalten:**
- Keine ID in Custom Field → kein Rendering, kein Error
- Ungültige ID → kein Rendering, kein Error
- Produkt inaktiv → kein Rendering, kein Error
- Produkt nicht im SalesChannel → kein Rendering, kein Error

**Test nach Implementierung:**
- Produkt mit Custom Field `node_linked_product_id` anlegen
- PDP aufrufen → Teaser erscheint unterhalb Buybox
- Ungültige ID setzen → kein Rendering
- Cache-Tags im Response-Header prüfen

---

## Milestone 4 – CMS-Element „Linked Product"

**Objective:**  
Erlebniswelten-Element mit Produktauswahl und Vorschau inkl. vollständigem Admin-Build-Setup.

**Technical Context:**  
`CmsElementDefinition`, `CmsSlotDataResolver`, Vue-Komponenten, Webpack-Build via `bin/build-administration.sh`.

**Requirements:**

1. **CMS Element Definition erstellen:** `src/Cms/Element/LinkedProductCmsElement.php`
   - Namespace: `Node\LinkedProduct\Cms\Element`
   - Extends: `AbstractCmsElementResolver`
   - `getType()`: `'node-linked-product'`
   - `collect()`: gibt `null` zurück
   - `enrich()`: leer (Logik im Resolver)

2. **CMS Element Resolver erstellen:** `src/Cms/Element/LinkedProductCmsResolver.php`
   - Namespace: `Node\LinkedProduct\Cms\Element`
   - Extends: `AbstractCmsElementResolver`
   - Constructor Injection: `LinkedProductLoader $linkedProductLoader`
   - `getType()`: `'node-linked-product'`
   - `collect()`: gibt `null` zurück
   - `enrich()` Logik:
     - Hole Config: `$slot->getFieldConfig()`
     - Hole Produkt-ID: `$config->get('productId')?->getStringValue()`
     - Wenn leer: `return`
     - Lade Produkt via `$linkedProductLoader->loadById()`
     - Wenn `null`: `return`
     - Setze Produkt: `$slot->getData()->set('product', $product)`

3. **Admin Entry Point:** `Resources/app/administration/src/main.js`
   - Import: `'./module/sw-cms/elements/node-linked-product'`

4. **Admin Element Registration:** `Resources/app/administration/src/module/sw-cms/elements/node-linked-product/index.js`
   - Import Component: `'./component'`
   - Import Config: `'./config'`
   - Registrierung via `Shopware.Service('cmsService').registerCmsElement()`
   - Config:
     - `name`: `'node-linked-product'`
     - `label`: `'sw-cms.elements.nodeLinkedProduct.label'`
     - `component`: `'sw-cms-el-node-linked-product'`
     - `configComponent`: `'sw-cms-el-config-node-linked-product'`
     - `defaultConfig`: `{ productId: { source: 'static', value: null } }`

5. **Admin Preview Component:**
   - Pfad: `Resources/app/administration/src/module/sw-cms/elements/node-linked-product/component/`
   - Dateien:
     - `index.js`: Registriert Vue-Komponente mit Template + SCSS Import
     - `sw-cms-el-node-linked-product.html.twig`: Vorschau-Template
     - `sw-cms-el-node-linked-product.scss`: Styling

6. **Preview Component Logik:**
   - Komponenten-Name: `'sw-cms-el-node-linked-product'`
   - Computed Property `product`: gibt `this.element?.data?.product` zurück
   - Template zeigt:
     - Bei Produkt vorhanden: Bild (wenn `product.cover`), Name, Preis (wenn `product.calculatedPrice`)
     - Bei leerem Produkt: Placeholder mit Snippet `'sw-cms.elements.nodeLinkedProduct.placeholder'`
   - SCSS: Einfaches Layout mit Padding, zentriert, Responsive

7. **Admin Config Component:**
   - Pfad: `Resources/app/administration/src/module/sw-cms/elements/node-linked-product/config/`
   - Dateien:
     - `index.js`: Registriert Config-Komponente
     - `sw-cms-el-config-node-linked-product.html.twig`: Config-Template

8. **Config Component Logik:**
   - Komponenten-Name: `'sw-cms-el-config-node-linked-product'`
   - Mixin: `'cms-element'`
   - Computed Property `productId`:
     - Getter: `this.element.config.productId.value`
     - Setter: Setzt Value + emittiert `'element-update'`
   - Template: `sw-entity-single-select` mit `entity="product"` und v-model auf `productId`

9. **Admin Snippets:**
   - Pfad: `Resources/app/administration/src/module/sw-cms/elements/node-linked-product/snippet/`
   - Dateien:
     - `de-DE.json`: Deutsche Übersetzungen
     - `en-GB.json`: Englische Übersetzungen
   - Snippet-Keys:
     - `sw-cms.elements.nodeLinkedProduct.label`: "Verknüpftes Produkt" / "Linked Product"
     - `sw-cms.elements.nodeLinkedProduct.placeholder`: "Wähle ein Produkt aus" / "Select a product"
     - `sw-cms.elements.nodeLinkedProduct.config.label`: "Produkt auswählen" / "Select Product"

10. **Service-Registrierung in `services.xml`:**
    - Service: `Node\LinkedProduct\Cms\Element\LinkedProductCmsElement`
      - Tag: `shopware.cms.data_resolver`
    - Service: `Node\LinkedProduct\Cms\Element\LinkedProductCmsResolver`
      - Argument: `Node\LinkedProduct\Core\Service\LinkedProductLoader`
      - Tag: `shopware.cms.data_resolver`

**Instructions for LLM:**

- Erzeuge ALLE Dateien mit vollständigem Code:
  - PHP: Element Definition + Resolver
  - JS: main.js, index.js (Element + Component + Config)
  - Twig: Preview-Template + Config-Template
  - SCSS: Preview-Styling
  - JSON: Snippets DE + EN

- Element Registration MUSS `defaultConfig` mit `productId` enthalten
- Preview Component MUSS Fallback für leeres Produkt zeigen
- Config Component MUSS `cms-element` Mixin verwenden
- Config MUSS `element-update` Event emittieren
- Beide Services MÜSSEN Tag `shopware.cms.data_resolver` haben
- Resolver MUSS `LinkedProductLoader` injiziert bekommen

**Build nach Implementierung:**
```bash
bin/build-administration.sh
bin/console cache:clear
```

**Test:**
- Admin öffnen → Erlebniswelten
- Neues Element verfügbar: "Verknüpftes Produkt"
- Produkt auswählbar via Dropdown
- Vorschau zeigt Produkt-Details
- Storefront → Element rendert korrekt

**Risiko:**
Ohne `main.js` Entry Point ist das CMS-Element NICHT sichtbar im Admin.

---

## Milestone 5 – Gemeinsames Twig-Partial & Styles

**Objective:**  
Einheitliches Teaser-Template für PDP und CMS mit SCSS-Build-Integration.

**Technical Context:**  
Twig, BEM-Naming, SCSS-Build via Webpack, `theme.json` für Theme-System.

**Requirements:**

1. **Teaser Template erstellen:** `Resources/views/storefront/component/node-linked-product/teaser.html.twig`

2. **Template-Struktur:**
   - Defensive Prüfung: `{% if product %}`
   - Wrapper: `<div class="node-linked-product-teaser">`
   - Link: `<a>` mit `seoUrl('frontend.detail.page', { productId: product.id })`
   - ARIA-Label: `aria-label` mit Snippet `'node.linked_product.link_aria'` (Parameter: `%name%`)
   - Bild-Container: `<div class="node-linked-product-teaser__image">`
     - Prüfung: `{% if product.cover and product.cover.media %}`
     - `<img>` mit:
       - `src`: `product.cover.media.url`
       - `alt`: `product.translated.name`
       - `loading="lazy"` (Performance)
       - CSS-Klasse: `node-linked-product-teaser__img`
   - Content-Container: `<div class="node-linked-product-teaser__content">`
     - Titel: `<h3>` mit `product.translated.name`
     - Preis: `{% if product.calculatedPrice %}` → `{{ product.calculatedPrice.unitPrice|currency }}`
     - Hersteller: `{% if product.manufacturer %}` → `product.manufacturer.translated.name`
   - BEM-Naming für alle Klassen

3. **Storefront Entry Point:** `Resources/app/storefront/src/main.js`
   - Import: `'./scss/linked-product.scss'`

4. **SCSS Datei:** `Resources/app/storefront/src/scss/linked-product.scss`

5. **SCSS-Struktur:**
   - Root-Klasse: `.node-linked-product-teaser`
     - Margin-top: `2rem`
     - Padding: `1.5rem`
     - Border: `1px solid #e0e0e0`
     - Border-radius: `8px`
     - Background: `#f9f9f9`
     - Hover-Effekt: `box-shadow` mit Transition
   - Link: `.node-linked-product-teaser__link`
     - Display: `flex`
     - Gap: `1.5rem`
     - Text-decoration: `none`
     - Color: `inherit`
     - Responsive: `@media (max-width: 768px)` → `flex-direction: column`
   - Image-Container: `.node-linked-product-teaser__image`
     - Flex-shrink: `0`
     - Width: `150px`
     - Height: `150px`
     - Overflow: `hidden`
     - Border-radius: `4px`
   - Image: `.node-linked-product-teaser__img`
     - Width: `100%`
     - Height: `100%`
     - Object-fit: `cover`
   - Content: `.node-linked-product-teaser__content`
     - Flex: `1`
   - Title: `.node-linked-product-teaser__title`
     - Margin: `0 0 0.5rem`
     - Font-size: `1.125rem`
     - Font-weight: `600`
   - Price: `.node-linked-product-teaser__price`
     - Font-size: `1.25rem`
     - Font-weight: `700`
     - Color: Theme-Variable oder `#333`
   - Manufacturer: `.node-linked-product-teaser__manufacturer`
     - Font-size: `0.875rem`
     - Color: `#666`

6. **Theme Config:** `Resources/theme.json`
   - JSON-Struktur:
     ```json
     {
       "style": ["@NodeLinkedProduct"],
       "script": ["@NodeLinkedProduct"]
     }
     ```

7. **Snippet-Dateien:**
   - `Resources/snippet/de-DE/node.linked_product.json`
   - `Resources/snippet/en-GB/node.linked_product.json`
   - Keys:
     - `node.linked_product.link_aria`: "Zum Produkt %name%" / "Go to product %name%"

**Instructions for LLM:**

- Erzeuge Twig-Template mit:
  - ALLEN defensiven Prüfungen (`if product`, `if cover`, etc.)
  - SEO-URL via `seoUrl()` Funktion
  - `loading="lazy"` für Performance
  - ARIA-Label für Accessibility
  - BEM-Naming für CSS-Klassen

- Erzeuge `main.js` mit SCSS-Import

- Erzeuge SCSS mit:
  - Vollständigem BEM-Pattern
  - Responsive Breakpoint bei 768px
  - Hover-Effekt mit Transition
  - Theme-Override-kompatiblem Styling

```markdown
   - **Konfiguration:**
     - Custom Field wird automatisch bei Installation erstellt
     - Admin: Produkt → Custom Fields → "Verknüpftes Produkt"
     - Produkt-ID via Entity-Select auswählen
   - **Deinstallation:**
     ```bash
     bin/console plugin:uninstall NodeLinkedProduct
     # Mit Datenlöschung:
     bin/console plugin:uninstall NodeLinkedProduct --no-keep-user-data
     ```
   - **Theme-Override:**
     - Template-Pfad: `<theme>/views/storefront/component/node-linked-product/teaser.html.twig`
     - SCSS-Override via Theme-Extension möglich
   - **Build-Befehle (bei Entwicklung):**
     ```bash
     bin/build-administration.sh  # Admin-Komponenten
     bin/build-storefront.sh      # Storefront-Styles
     ```
   - **Lizenz:** Proprietary
   - **Version:** 1.0.0
   - **Maintainer:** Node / Harry Gabriel

5. **CHANGELOG.md erstellen:**

   **Format:**
   ```markdown
   # Changelog
   Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

   ## [1.0.0] - 2024-11-09

   ### Added
   - Initiales Release
   - Custom Field `node_linked_product_id` für Produkt-Verknüpfung
   - Service `LinkedProductLoader` zum Laden von Produkten im SalesChannel-Kontext
   - PDP-Integration: Anzeige des verknüpften Produkts unterhalb der Buybox
   - CMS-Element "Linked Product" für Erlebniswelten
   - Admin-Konfiguration mit Entity-Select
   - Responsive Teaser-Template mit BEM-Naming
   - Cache-Tag-Integration für automatische Invalidierung
   - Unit-Tests für Loader, Subscriber, Resolver
   - Mehrsprachigkeit (DE/EN)
   - Theme-Override-Kompatibilität

   ### Technical
   - Shopware 6.6+ Kompatibilität
   - PHP 8.2+ Unterstützung
   - PSR-4 Autoloading
   - Doctrine DBAL Migrations
   - Vue.js Admin-Komponenten
   - SCSS mit Webpack-Build
   ```

6. **composer.json erweitern:**

   **Scripts hinzufügen:**
   ```json
   "scripts": {
       "test": "phpunit --configuration phpunit.xml.dist",
       "cs-fix": "php-cs-fixer fix"
   }
   ```

   **Dev-Dependencies (optional):**
   ```json
   "require-dev": {
       "phpunit/phpunit": "^9.5",
       "friendsofphp/php-cs-fixer": "^3.0"
   }
   ```

7. **PHPUnit-Konfiguration:** `phpunit.xml.dist`

   **Struktur:**
   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
            bootstrap="tests/bootstrap.php"
            colors="true">
       <testsuites>
           <testsuite name="Unit">
               <directory>tests/Unit</directory>
           </testsuite>
       </testsuites>
       <coverage>
           <include>
               <directory suffix=".php">src</directory>
           </include>
       </coverage>
   </phpunit>
   ```

8. **Test-Bootstrap:** `tests/bootstrap.php`
   - Autoloader laden
   - Shopware-Test-Environment initialisieren (falls benötigt)

**Instructions for LLM:**

**Unit-Tests:**
- Erzeuge ALLE 3 Test-Klassen mit vollständigen Test-Cases
- Verwende PHPUnit-Mocks für alle Dependencies
- Mock für Repository MUSS `EntitySearchResult` mit `SalesChannelProductEntity` zurückgeben
- Verwende `$this->createMock()` für Mocking
- Assertions MÜSSEN prüfen:
  - Return-Types korrekt
  - Null-Handling funktioniert
  - Associations werden gesetzt
  - Filter werden gesetzt
  - Cache-Tags im korrekten Format

**Snippets:**
- Erzeuge beide JSON-Dateien (DE + EN)
- Verwende verschachtelte JSON-Struktur (`node.linked_product.key`)
- Parameter-Syntax: `%name%` für Platzhalter

**README:**
- Vollständige Markdown-Datei mit allen Abschnitten
- Code-Blöcke mit Syntax-Highlighting (`bash`)
- Klare Abschnitt-Struktur mit H2/H3
- Praktische Beispiele für alle Use-Cases

**CHANGELOG:**
- Semantic Versioning: `[1.0.0]`
- Datumsformat: `YYYY-MM-DD`
- Kategorien: `Added`, `Changed`, `Fixed`, `Removed`
- Erste Version MUSS `Added` + `Technical` Sektion haben

**PHPUnit-Config:**
- Bootstrap-Pfad korrekt
- Testsuite für Unit-Tests
- Coverage nur für `src/` Verzeichnis
- XML-Schema-Validierung

**Cache-Strategie:**
- Key-Generierung MUSS alle Context-Parameter enthalten
- Tags MUSS Format `product.{id}` verwenden
- Dokumentiere Invalidierungs-Mechanismus

**Test-Execution:**
```bash
composer test
# oder
vendor/bin/phpunit
```

**Code-Quality:**
```bash
composer cs-fix
```

---

## Erfolgskennzahlen (KPIs)

**Funktional:**
- Kein Rendering bei ungültiger Produkt-ID
- Custom Field funktioniert unabhängig vom CMS-Element
- CMS-Element funktioniert unabhängig vom Custom Field
- Beide Integrationspunkte nutzen denselben Loader-Service

**Performance:**
- Page Load Impact < 5ms (Cache-Warm)
- Lazy-Loading für Bilder aktiv
- Cache-Tags korrekt für automatische Invalidierung

**Code-Qualität:**
- 100% Autoload/Namespace-Kompatibilität
- Alle Services korrekt registriert
- Keine Hard-Coded Values
- BEM-Naming konsistent

**Installation:**
- Installation fehlerfrei
- Deinstallation entfernt alle Daten (bei `--no-keep-user-data`)
- Migration läuft ohne Fehler
- Build-Prozesse erfolgreich

**Tests:**
- Alle Unit-Tests erfolgreich
- Code-Coverage > 80% für Core-Services
- Mock-Tests für alle externe Dependencies

---

## Gesamt-Arbeitsreihenfolge

**Sequenzielle Abarbeitung:**

1. **Milestone 1 ausführen:**
   - Alle 4 Basis-Dateien erstellen
   - Migration testen via `plugin:install`
   - Admin prüfen: Custom Field sichtbar als Entity-Select

2. **Milestone 2 erstellen:**
   - Loader-Service implementieren
   - Service registrieren
   - Return-Type `SalesChannelProductEntity` validieren
   - Associations + Filter prüfen

3. **Milestone 3 implementieren:**
   - Subscriber erstellen
   - Template erstellen mit `sw_extends`
   - Event-Registrierung testen
   - PDP aufrufen → Teaser validieren
   - Cache-Tags im Response-Header prüfen

4. **Milestone 4 umsetzen:**
   - CMS Element + Resolver erstellen
   - Alle Admin-Dateien erstellen (main.js, index.js, Komponenten)
   - Snippets für Admin hinzufügen
   - Build ausführen: `bin/build-administration.sh`
   - Admin testen: Element verfügbar + funktional
   - Storefront testen: Element rendert korrekt

5. **Milestone 5 finalisieren:**
   - Teaser-Template erstellen
   - SCSS implementieren
   - Storefront `main.js` + `theme.json` erstellen
   - Build ausführen: `bin/build-storefront.sh`
   - Responsive-Tests durchführen
   - Styling in PDP + CMS validieren

6. **Milestone 6 abschließen:**
   - Alle Unit-Tests schreiben
   - Tests ausführen: `composer test`
   - Snippets für Storefront hinzufügen
   - README.md schreiben
   - CHANGELOG.md erstellen
   - PHPUnit-Config hinzufügen
   - Gesamttest: Installation → Nutzung → Deinstallation

**Zwischen jedem Milestone:**
```bash
bin/console cache:clear
```

**Nach Milestone 4 + 5:**
```bash
bin/build-administration.sh
bin/build-storefront.sh
bin/console cache:clear
```

---

## Installation (nach Fertigstellung)

**Standard-Installation:**
```bash
bin/console plugin:refresh
bin/console plugin:install -a NodeLinkedProduct
bin/console cache:clear
```

**Mit Build (bei Entwicklung):**
```bash
bin/console plugin:refresh
bin/console plugin:install NodeLinkedProduct
bin/build-administration.sh
bin/build-storefront.sh
bin/console plugin:activate NodeLinkedProduct
bin/console cache:clear
```

**Validierung:**
- Admin öffnen → Produkt bearbeiten → Custom Field sichtbar
- Admin öffnen → Erlebniswelten → Element "Verknüpftes Produkt" verfügbar
- Storefront → PDP mit Custom Field → Teaser sichtbar
- Storefront → CMS-Seite mit Element → Produkt angezeigt

---

## Deinstallation

**Mit Datenlöschung:**
```bash
bin/console plugin:uninstall NodeLinkedProduct --no-keep-user-data
bin/console cache:clear
```

**Ohne Datenlöschung:**
```bash
bin/console plugin:uninstall NodeLinkedProduct
bin/console cache:clear
```

**Was wird entfernt (mit `--no-keep-user-data`):**
- Custom Field `node_linked_product_id`
- Custom Field Set `node_linked_product`
- Custom Field Set Relation zu `product`

**Was bleibt erhalten:**
- Produkt-Daten (Custom Field Values werden automatisch entfernt)

---

## Debugging & Troubleshooting

**Custom Field nicht sichtbar:**
- Migration prüfen: `bin/console migration:status`
- Cache leeren: `bin/console cache:clear`
- Datenbank prüfen: `SELECT * FROM custom_field WHERE name = 'node_linked_product_id'`

**CMS-Element nicht verfügbar:**
- Admin-Build prüfen: `bin/build-administration.sh`
- Browser-Cache leeren (Hard-Refresh)
- Console-Errors im Browser-DevTools prüfen
- `main.js` Import-Pfad validieren

**Teaser nicht sichtbar auf PDP:**
- Custom Field Wert gesetzt?
- Produkt-ID gültig?
- Produkt aktiv?
- Produkt im SalesChannel sichtbar?
- Event-Subscriber registriert? → `bin/console debug:event-dispatcher ProductPageLoadedEvent`

**Styles nicht geladen:**
- Storefront-Build: `bin/build-storefront.sh`
- `theme.json` vorhanden?
- `main.js` Import korrekt?
- Theme neu kompilieren: `bin/console theme:compile`

**Cache-Tags fehlen:**
- Response-Header prüfen: `curl -I <url> | grep cache-tags`
- Format validieren: `product.{id}` (mit Punkt)
- HTTP-Cache aktiv?

---

## Erweiterungen (Future Roadmap)

**v1.1.0 – AddToCart-Integration:**
- "In den Warenkorb"-Button im Teaser
- AJAX-Integration ohne Page-Reload
- Flash-Message bei Erfolg

**v1.2.0 – RuleBuilder-Extension:**
- Bedingung "Produkt hat verknüpftes Produkt"
- Bedingung "Verknüpftes Produkt ist X"
- Verwendung in Promotions/Flow-Builder

**v1.3.0 – Multi-Product-Support:**
- Custom Field als Collection
- Slider/Carousel für mehrere Produkte
- Konfigurierbare Anzahl

**v1.4.0 – Analytics-Integration:**
- Tracking von Klicks auf verknüpfte Produkte
- Conversion-Rate-Messung
- Google Analytics Events

**v1.5.0 – API-Extension:**
- Store-API-Route für verknüpfte Produkte
- Admin-API-Extension
- Webhook bei Verknüpfung

---

## Lizenz & Versionierung

**Lizenz:** Proprietary  
**Version:** 1.0.0 (MVP)  
**Release-Datum:** 2024-11-09  
**Nächstes Ziel:** v1.1.0 → AddToCart + RuleBuilder-Erweiterung

**Semantic Versioning:**
- MAJOR: Breaking Changes (z.B. Shopware 7 Migration)
- MINOR: Neue Features (z.B. AddToCart-Button)
- PATCH: Bugfixes (z.B. Cache-Tag-Korrektur)

---

## Maintainer

**Vendor:** Node  
**Author:** Harry Gabriel  
**Namespace:** `Node\LinkedProduct`  
**Support:** Via Issue-Tracker (falls vorhanden)

---

## Technische Spezifikationen

**Dependencies:**
- Shopware Core: `>=6.6.0 <6.8.0`
- PHP: `>=8.2`
- Doctrine DBAL: via Shopware
- Vue.js: via Shopware Admin

**Keine externen Dependencies:**
- Keine Composer-Packages außer Shopware
- Keine NPM-Packages außer Shopware-Defaults

**Browser-Support:**
- Chrome/Edge: Letzte 2 Versionen
- Firefox: Letzte 2 Versionen
- Safari: Letzte 2 Versionen
- Mobile: iOS 14+, Android 10+

**Accessibility:**
- ARIA-Labels für Links
- Keyboard-Navigation unterstützt
- Screen-Reader-kompatibel
- WCAG 2.1 Level AA konform

---

## Kritische Anforderungen (Must-Have)

1. **Custom Field Type MUSS `entity` sein:**
   - Typ `text` funktioniert nicht mit Entity-Select
   - Admin-Komponente erfordert `componentName: 'sw-entity-single-select'`

2. **Return Type MUSS `SalesChannelProductEntity` sein:**
   - `ProductEntity` fehlt SalesChannel-spezifische Daten
   - Preise/Visibility nur in `SalesChannelProductEntity` korrekt

3. **Cache-Tag-Format MUSS `product.{id}` sein:**
   - Shopware 6.6+ nutzt Punkt-Notation
   - Format `product-{id}` funktioniert nicht für Invalidierung

4. **Admin MUSS `main.js` Entry Point haben:**
   - Ohne Entry Point keine Element-Registrierung
   - Webpack kann Module nicht finden

5. **Template MUSS `sw_extends` verwenden:**
   - `extend` ist veraltete Syntax
   - Moderne Shopware-Themes nutzen `sw_extends`/`sw_include`

6. **Migration MUSS `Uuid::randomBytes()` verwenden:**
   - String-IDs funktionieren nicht
   - Doctrine erwartet Binary-UUIDs

7. **Visibility-Filter MUSS gesetzt sein:**
   - Ohne Filter sind Produkte in allen SalesChannels sichtbar
   - Security-Risiko bei Multi-Shop-Setups

8. **Fallback MUSS graceful sein:**
   - Keine Exceptions bei ungültigen IDs
   - Kein Error-Log bei normalem Verhalten

---

## Code-Style-Richtlinien

**PHP:**
- PSR-12 Code-Style
- Type-Hints für alle Parameter + Return-Types
- Strict-Types in jeder Datei: `declare(strict_types=1);`
- Keine Short-Array-Syntax in alten Dateien

**JavaScript/Vue:**
- ES6+ Syntax
- Single-File-Components
- Composition API bevorzugt (falls Vue 3)
- Destructuring für Props

**Twig:**
- 4 Spaces Indentation
- `sw_extends`/`sw_include` statt alter Syntax
- Defensive Checks mit `if`
- Keine Logik im Template (nur Präsentation)

**SCSS:**
- BEM-Naming-Convention
- Verschachtelte Selektoren max. 3 Ebenen
- Variablen für Farben/Abstände
- Mobile-First Media-Queries

**Naming-Conventions:**
- PHP-Klassen: PascalCase
- PHP-Methoden: camelCase
- PHP-Properties: camelCase
- Twig-Variablen: camelCase
- CSS-Klassen: kebab-case (BEM)
- SCSS-Variablen: kebab-case
- JS-Variablen: camelCase
- Vue-Komponenten: kebab-case

---

## Finale Checkliste

**Vor Release v1.0.0:**

- [ ] Alle 6 Milestones implementiert
- [ ] Alle Dateien erstellt (siehe Projektstruktur)
- [ ] Migration funktioniert fehlerfrei
- [ ] Custom Field im Admin sichtbar als Entity-Select
- [ ] Loader-Service registriert + funktional
- [ ] PDP-Integration zeigt Teaser korrekt
- [ ] CMS-Element im Admin verfügbar
- [ ] CMS-Element auf Storefront funktional
- [ ] Teaser-Template responsive
- [ ] SCSS korrekt geladen
- [ ] Alle Unit-Tests erfolgreich
- [ ] Snippets DE + EN vorhanden
- [ ] README.md vollständig
- [ ] CHANGELOG.md vorhanden
- [ ] Cache-Tags korrekt gesetzt
- [ ] Deinstallation entfernt alle Daten
- [ ] Build-Befehle dokumentiert
- [ ] Theme-Override getestet
- [ ] Browser-Kompatibilität geprüft
- [ ] Accessibility validiert
- [ ] Performance < 5ms gemessen
- [ ] Keine PHP-Errors im Log
- [ ] Keine JS-Errors in Console

**Test-Szenarien:**

1. **Happy Path PDP:**
   - Produkt mit gültiger Custom Field ID → Teaser angezeigt

2. **Happy Path CMS:**
   - Element mit gültiger Produkt-ID → Produkt angezeigt

3. **Edge Case – Leere ID:**
   - Kein Custom Field Value → kein Rendering, kein Error

4. **Edge Case – Ungültige ID:**
   - Custom Field mit UUID die nicht existiert → kein Rendering, kein Error

5. **Edge Case – Inaktives Produkt:**
   - Verknüpftes Produkt ist `active=false` → kein Rendering

6. **Edge Case – Falscher SalesChannel:**
   - Produkt nicht im aktuellen SalesChannel → kein Rendering

7. **Performance:**
   - 100 Produkte mit verknüpften Produkten → Page Load < 500ms

8. **Cache:**
   - Produkt ändern → Cache invalidiert → neuer Content sichtbar

---

**Ende der Instruktionen**
