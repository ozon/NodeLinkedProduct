<?php declare(strict_types=1);

namespace Node\LinkedProduct\Cms\Element;

use Node\LinkedProduct\Core\Service\LinkedProductLoader;
use Shopware\Core\Content\Cms\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\CmsSlotEntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class LinkedProductCmsResolver extends AbstractCmsElementResolver
{
    private const CMS_TYPE = 'node-linked-product';
    private const CONFIG_KEY_PRODUCT_ID = 'product';

    private LinkedProductLoader $linkedProductLoader;

    public function __construct(LinkedProductLoader $linkedProductLoader)
    {
        $this->linkedProductLoader = $linkedProductLoader;
    }

    public function getType(): string
    {
        return self::CMS_TYPE;
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $productId = $this->resolveConfiguredProductId($slot);
        if ($productId === null) {
            return null;
        }

        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('cover.media');
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('prices');

        $collection = new CriteriaCollection();
        $collection->add(self::CMS_TYPE . '-' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);

        return $collection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $element = new LinkedProductCmsElement();
        $productId = $this->resolveConfiguredProductId($slot);

        if ($productId === null) {
            $slot->setData($element);
            return;
        }

        $salesChannelContext = $resolverContext->getSalesChannelContext();
        $product = $this->linkedProductLoader->loadById($productId, $salesChannelContext);
        $element->setProduct($product);

        $slot->setData($element);
    }

    private function resolveConfiguredProductId(CmsSlotEntity $slot): ?string
    {
        $fieldConfig = $slot->getFieldConfig();
        if ($fieldConfig === null || !$fieldConfig->has(self::CONFIG_KEY_PRODUCT_ID)) {
            return null;
        }

        $configField = $fieldConfig->get(self::CONFIG_KEY_PRODUCT_ID);
        if ($configField === null) {
            return null;
        }

        $value = $configField->getValue();

        return $value;
    }
}
