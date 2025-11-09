<?php declare(strict_types=1);

namespace Node\LinkedProduct\Storefront\Subscriber;

use Node\LinkedProduct\Cms\Element\LinkedProductCmsElement;
use Node\LinkedProduct\Core\Service\LinkedProductLoader;
use Node\LinkedProduct\NodeLinkedProduct;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageSubscriber implements EventSubscriberInterface
{
    private LinkedProductLoader $linkedProductLoader;

    public function __construct(LinkedProductLoader $linkedProductLoader)
    {
        $this->linkedProductLoader = $linkedProductLoader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();
        $customFields = $product->getCustomFields();

        if (empty($customFields) || !isset($customFields[NodeLinkedProduct::LINKED_PRODUCT_CUSTOM_FIELD_NAME])) {
            // No custom field set, do nothing
            return;
        }

        $linkedProductId = $customFields[NodeLinkedProduct::LINKED_PRODUCT_CUSTOM_FIELD_NAME];

        if (empty($linkedProductId)) {
            return;
        }

        $linkedProduct = $this->linkedProductLoader->loadById($linkedProductId, $event->getSalesChannelContext());

        // Product not found, not active, or not visible
        if ($linkedProduct === null) {
            return;
        }

        $element = new LinkedProductCmsElement();
        $element->setProduct($linkedProduct);
        $event->getPage()->addExtension('nodeLinkedProduct', $element);
    }
}
