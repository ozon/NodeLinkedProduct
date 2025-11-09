# Node LinkedProduct – Projektagenda  
Shopware 6.6 / 6.7 Plugin – Anzeige eines verknüpften Produkts auf PDP & in Erlebniswelten

---

## Ziel
Ein Plugin, das es ermöglicht, über eine Produkt-ID ein zusätzliches Produkt in der Produktdetailseite (PDP) und in Erlebniswelten (CMS) anzuzeigen.  
Die Produkt-ID kann entweder im Produkt über ein Custom Field oder in der CMS-Element-Konfiguration definiert werden.

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

NodeLinkedProduct/ ├── composer.json ├── NodeLinkedProduct.php ├── Resources/ │   ├── config/services.xml │   ├── app/ │   │   ├── administration/... │   │   └── storefront/src/scss/linked-product.scss │   └── views/storefront/ │       ├── component/node-linked-product/teaser.html.twig │       └── page/product-detail/buy-widget.html.twig └── src/ ├── Core/Service/LinkedProductLoader.php ├── Cms/Element/LinkedProductCmsElement.php ├── Cms/Element/LinkedProductCmsResolver.php ├── Storefront/Subscriber/ProductPageSubscriber.php └── Migration/Migration202511090001LinkedProductCustomField.php

---

## Gesamtübersicht Milestones

| Nr | Titel | Ziel |
|----|-------|------|
| **1** | Plugin-Grundgerüst & Custom-Field | Funktionsfähiges Plugin + Migration für `node_linked_product_id` |
| **2** | Loader-Service | Service zum Laden eines Produkts im SalesChannel-Kontext |
| **3** | PDP-Integration | Anzeige des verknüpften Produkts auf der Produktdetailseite |
| **4** | CMS-Element | Erlebniswelten-Element „Linked Product“ mit Produktauswahl |
| **5** | Twig-Partial & Styles | Einheitliches Design für PDP & CMS |
| **6** | Qualität, Tests & Dokumentation | Snippets, Cache-Tags, Unit-Tests & README |

---

## Milestone 1 – Plugin-Grundgerüst & Custom-Field
**Objective:**  
Funktionsfähiges Shopware-Plugin mit Migration für Produkt-Custom-Field `node_linked_product_id`.

**Technical Context:**  
PSR-4, Composer, Doctrine DBAL MigrationStep, UUID-Handling.

**Requirements:**
1. Plugin-Ordner: `/custom/plugins/NodeLinkedProduct/`
2. Namespace: `Node\LinkedProduct`
3. Dateien:
   - `composer.json`
   - `NodeLinkedProduct.php`
   - `Resources/config/services.xml`
   - `src/Migration/Migration202511090001LinkedProductCustomField.php`
4. Custom-Field-Set: **Linked Product** → Entity: product
5. Feld: **node_linked_product_id** (type: text)
6. Deinstallationslogik: Entfernt Feld/Set, wenn `keepUserData=false`

**Instructions for LLM:**
- Erzeuge alle genannten Dateien mit validem Code.
- Nach Installation Admin prüfen → Custom Field sichtbar.
- Test: `bin/console plugin:refresh && bin/console plugin:install -a NodeLinkedProduct && bin/console cache:clear`

---

## Milestone 2 – Loader-Service
**Objective:**  
Einheitlicher Service zum Laden eines Produkts anhand einer ID im SalesChannel-Kontext.

**Technical Context:**  
SalesChannelRepository, Criteria, Associations.

**Requirements:**
1. Klasse: `src/Core/Service/LinkedProductLoader.php`
2. Methode:  
   ```php
   public function loadById(string $id, SalesChannelContext $context): ?ProductEntity

3. Lädt Produkt inkl. Associations (cover.media, prices, manufacturer).


4. Prüft active & visibility.


5. Gibt null zurück, wenn Produkt ungültig.



Instructions for LLM:

Erzeuge Service + services.xml-Registrierung.

Unit-Test mit Mock-Repository.



---

Milestone 3 – PDP-Integration unter Buybox

Objective:
Zeigt das Zusatzprodukt auf der Produktdetailseite unterhalb des „In den Warenkorb“-Bereichs an.

Technical Context:
ProductPageLoadedEvent, Twig-Extend.

Requirements:

1. Subscriber: src/Storefront/Subscriber/ProductPageSubscriber.php

Event: ProductPageLoadedEvent

Logik:

Lese node_linked_product_id aus Produkt-Custom-Field.

Lade Produkt via LinkedProductLoader.

Übergib an Twig als Page-Extension nodeLinkedProduct.




2. Template: Resources/views/storefront/page/product-detail/buy-widget.html.twig

Extend page_product_detail_buy.

Füge Include des Teasers hinzu:

{% if page.extensions.nodeLinkedProduct %}
  {% include '@Storefront/component/node-linked-product/teaser.html.twig' with { product: page.extensions.nodeLinkedProduct } %}
{% endif %}



3. Cache-Tags: product-{linkedId} hinzufügen.



Instructions for LLM:

Erzeuge Subscriber + Twig-Template-Erweiterung.

Prüfe, dass nichts rendert bei ungültiger ID.



---

Milestone 4 – CMS-Element „Linked Product“

Objective:
Erlebniswelten-Element mit Produktauswahl und Vorschau.

Technical Context:
CmsElementDefinition, Resolver, Admin (Vue).

Requirements:

1. Definition: src/Cms/Element/LinkedProductCmsElement.php

Element-Name: node-linked-product



2. Resolver: src/Cms/Element/LinkedProductCmsResolver.php

Lädt Produkt via LinkedProductLoader.

Liefert DataCollection + Cache-Tags.



3. Admin:

Pfad: Resources/app/administration/src/module/sw-cms/elements/node-linked-product/

Dateien:

index.js

component/sw-cms-el-node-linked-product.vue (Preview)

config/sw-cms-el-config-node-linked-product.vue (entity-single-select)


Snippets: de-DE/en-GB




Instructions for LLM:

Erzeuge Definition/Resolver/Admin-Registrierung.

Vorschau: kleine Karte (Bild, Name, Preis).



---

Milestone 5 – Gemeinsames Twig-Partial & Styles

Objective:
Einheitliches Teaser-Template für PDP und CMS.

Technical Context:
Twig, BEM-Naming, Theme-Override-kompatibel.

Requirements:

1. Datei: Resources/views/storefront/component/node-linked-product/teaser.html.twig


2. Inhalt:

Bild: product.cover.media.url

Name: product.translated.name

Preis: product.calculatedPrice.unitPrice

Link: seoUrl('frontend.detail.page', { productId: product.id })

Defensive Checks (if product is not null).



3. Optional:

SCSS-Datei: Resources/app/storefront/src/scss/linked-product.scss

Build-Registrierung im Plugin.




Instructions for LLM:

Erzeuge Twig + SCSS-Datei.

Responsives Layout mit .node-linked-product-teaser.



---

Milestone 6 – Qualität, Tests & Dokumentation

Objective:
Tests, Snippets, Cache-Tags, README, Changelog.

Technical Context:
PHPUnit, Snippets, Markdown.

Requirements:

1. Unit-Tests für Loader & Resolver.


2. Snippets:

Resources/snippet/de-DE/node.linked_product.json

Resources/snippet/en-GB/node.linked_product.json



3. Caching:

Key: productId + salesChannelId + languageId + currencyId

Tags: product-{linkedId}



4. README.md:

Installation, Nutzung (PDP/CMS), Uninstall, Theme-Override.



5. CHANGELOG.md:

Version 1.0.0 → „Initial Release“




Instructions for LLM:

Erzeuge Tests (PHPUnit Mock Repository).

Erstelle Snippets + README + Changelog.



---

Erfolgskennzahlen (KPIs)

Kein Rendering bei ungültiger Produkt-ID

Page Load Impact < 5ms (Cache-Warm)

Installation & Uninstall fehlerfrei

Custom Field & CMS-Element funktionieren unabhängig

100% Autoload/Namespace-Kompatibilität



---

Gesamt-Arbeitsreihenfolge

1. Milestone 1 ausführen → Migration testen


2. Milestone 2 erstellen → Loader-Service prüfen


3. Milestone 3 → PDP-Integration & Rendering validieren


4. Milestone 4 → CMS-Element & Preview testen


5. Milestone 5 → Teaser & Styles verfeinern


6. Milestone 6 → Doku, Snippets, Tests & Release v1.0.0




---

Installation (nach Fertigstellung)

bin/console plugin:refresh
bin/console plugin:install -a NodeLinkedProduct
bin/console cache:clear


---

Deinstallation

bin/console plugin:uninstall NodeLinkedProduct --no-keep-user-data

Entfernt Custom Field Set & Feld vollständig.


---

Lizenz & Versionierung

Lizenz: Proprietary

Version: 1.0.0 (MVP)

Nächstes Ziel: v1.1.0 → AddToCart + RuleBuilder-Erweiterung



---

Maintainer

Vendor: Node
Author: Harry Gabriel
Kontakt: —
Namespace: Node\LinkedProduct
