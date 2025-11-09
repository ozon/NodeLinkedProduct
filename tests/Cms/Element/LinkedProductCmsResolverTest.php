<?php

namespace Node\\LinkedProduct\\Test\\Cms\\Element;

use Node\\LinkedProduct\\Cms\\Element\\LinkedProductCmsElement;
use Node\\LinkedProduct\\Cms\\Element\\LinkedProductCmsResolver;
use Node\\LinkedProduct\\Core\\Service\\LinkedProductLoader;
use PHPUnit\\Framework\\TestCase;
use Shopware\\Core\\Content\\Cms\\Aggregate\\CmsSlot\\CmsSlotEntity;
use Shopware\\Core\\Content\\Cms\\DataResolver\\CriteriaCollection;
use Shopware\\Core\\Content\\Cms\\DataResolver\\ElementDataCollection;
use Shopware\\Core\\Content\\Cms\\DataResolver\\FieldConfig;
use Shopware\\Core\\Content\\Cms\\DataResolver\\FieldConfigCollection;
use Shopware\\Core\\Content\\Cms\\DataResolver\\ResolverContext\\ResolverContext;
use Shopware\\Core\\System\\SalesChannel\\SalesChannelContext;
use Symfony\\Component\\HttpFoundation\\Request;

class LinkedProductCmsResolverTest extends TestCase
{
    public function testCollectCreatesCriteriaForConfiguredProduct(): void
    {
        $slot = new CmsSlotEntity();
        $slot->setId('slot-id');
        $slot->setFieldConfig(new FieldConfigCollection([
            new FieldConfig(LinkedProductCmsResolver::CONFIG_KEY_PRODUCT_ID, FieldConfig::SOURCE_STATIC, 'linked-id'),
        ]));

        $resolver = new LinkedProductCmsResolver($this->createMock(LinkedProductLoader::class));
        $context = $this->createResolverContext();

        $collection = $resolver->collect($slot, $context);

        static::assertInstanceOf(CriteriaCollection::class, $collection);
        $all = $collection->all();
        static::assertNotEmpty($all);
    }

    public function testEnrichAddsElementToSlotWhenLoaderCalled(): void
    {
        $slot = new CmsSlotEntity();
        $slot->setId('slot-id');
        $slot->setFieldConfig(new FieldConfigCollection([
            new FieldConfig(LinkedProductCmsResolver::CONFIG_KEY_PRODUCT_ID, FieldConfig::SOURCE_STATIC, 'linked-id'),
        ]));

        $loader = $this->createMock(LinkedProductLoader::class);
        $loader->expects(static::once())
            ->method('loadById')
            ->with('linked-id');

        $resolver = new LinkedProductCmsResolver($loader);
        $context = $this->createResolverContext();

        $resolver->enrich($slot, $context, new ElementDataCollection());

        static::assertInstanceOf(LinkedProductCmsElement::class, $slot->getData());
    }

    private function createResolverContext(): ResolverContext
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        return new ResolverContext($salesChannelContext, new Request());
    }
}
