# NodeLinkedProduct

The **Node Linked Product** plugin displays a secondary product on the product detail page and provides a CMS element that can be used inside Shopping Experiences to highlight complementary items.

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install -a NodeLinkedProduct
bin/console cache:clear
```

## Usage

### Product detail page

Add a product ID to the custom field `node_linked_product_id` of any product. The linked product will automatically appear underneath the buy box on the PDP.

### Shopping experiences element

In the CMS, add the "Linked Product" element to a slot and select the product that should be displayed. The element uses the same teaser layout as on the PDP.

## Uninstallation

```bash
bin/console plugin:uninstall NodeLinkedProduct --no-keep-user-data
```

Uninstalling the plugin without keeping user data removes the custom field and custom field set automatically.

## Theme adjustments

Override the Twig blocks in `@Storefront/storefront/component/node-linked-product/teaser.html.twig` to adjust the markup or extend the SCSS file located at `src/Resources/app/storefront/src/scss/linked-product.scss` for styling.
