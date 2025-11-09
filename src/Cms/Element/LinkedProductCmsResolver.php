<?php

declare(strict_types=1);

namespace Node\LinkedProduct\Cms\Element;

use Node\LinkedProduct\Core\Service\LinkedProductLoader;
use Node\LinkedProduct\NodeLinkedProduct;
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
    public const CMS_TYPE = 'node-linked-product';
    public const CONFIG_KEY_PRODUCT_ID = 'linkedProductId';

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
        $productId = $this->resolveConfiguredProductId($slot, $resolverContext);
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
        $productId = $this->resolveConfiguredProductId($slot, $resolverContext);
        if ($productId === null) {
            return;
        }

        $salesChannelContext = $resolverContext->getSalesChannelContext();
        $element = new LinkedProductCmsElement();

        if ($salesChannelContext instanceof SalesChannelContext) {
            $product = $this->linkedProductLoader->loadById($productId, $salesChannelContext);
            $element->setProduct($product);
        }

        $slot->setData($element);
    }

    private function resolveConfiguredProductId(CmsSlotEntity $slot, ResolverContext $resolverContext): ?string
    {
        $fieldConfig = $slot->getFieldConfig();
        if ($fieldConfig !== null && $fieldConfig->has(self::CONFIG_KEY_PRODUCT_ID)) {
            $configField = $fieldConfig->get(self::CONFIG_KEY_PRODUCT_ID);
            $value = null;

            if ($configField instanceof FieldConfig) {
                $value = $configField->getValue();
            } elseif (is_array($configField) && isset($configField['value'])) {
                $value = $configField['value'];
            } else {
                $value = $configField;
            }

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        if ($resolverContext instanceof CmsSlotEntityResolverContext) {
            $entity = $resolverContext->getEntity();
            if ($entity !== null && method_exists($entity, 'getCustomFields')) {
                $customFields = $entity->getCustomFields();
                if (is_array($customFields)) {
                    $value = $customFields[NodeLinkedProduct::CUSTOM_FIELD_NAME] ?? null;
                    if (is_string($value) && $value !== '') {
                        return $value;
                    }
                }
            }
        }

        return null;
    }
}
