<?php

namespace Node\\LinkedProduct\\Test\\Core\\Service;

use Node\\LinkedProduct\\Core\\Service\\LinkedProductLoader;
use PHPUnit\\Framework\\TestCase;
use Shopware\\Core\\Content\\Product\\Aggregate\\ProductVisibility\\ProductVisibilityCollection;
use Shopware\\Core\\Content\\Product\\Aggregate\\ProductVisibility\\ProductVisibilityEntity;
use Shopware\\Core\\Content\\Product\\ProductCollection;
use Shopware\\Core\\Content\\Product\\ProductEntity;
use Shopware\\Core\\Content\\Product\\ProductVisibilityDefinition;
use Shopware\\Core\\Framework\\Context;
use Shopware\\Core\\Framework\\DataAbstractionLayer\\Search\\Criteria;
use Shopware\\Core\\Framework\\DataAbstractionLayer\\Search\\EntitySearchResult;
use Shopware\\Core\\System\\SalesChannel\\Entity\\SalesChannelRepository;
use Shopware\\Core\\System\\SalesChannel\\SalesChannelContext;
use Shopware\\Core\\System\\SalesChannel\\SalesChannelEntity;

class LinkedProductLoaderTest extends TestCase
{
    public function testLoadByIdReturnsNullWhenRepositoryHasNoProduct(): void
    {
        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                'product',
                0,
                new ProductCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn('sales-channel');

        $loader = new LinkedProductLoader($repository);

        static::assertNull($loader->loadById('product-id', $context));
    }

    public function testLoadByIdReturnsNullWhenProductInactive(): void
    {
        $product = new ProductEntity();
        $product->setId('product-id');
        $product->setActive(false);

        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                'product',
                1,
                new ProductCollection([$product]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn('sales-channel');

        $loader = new LinkedProductLoader($repository);

        static::assertNull($loader->loadById('product-id', $context));
    }

    public function testLoadByIdReturnsProductWhenActiveAndVisible(): void
    {
        $salesChannelId = 'sales-channel';
        $product = new ProductEntity();
        $product->setId('product-id');
        $product->setActive(true);

        $visibility = new ProductVisibilityEntity();
        $visibility->setSalesChannelId($salesChannelId);
        $visibility->setVisibility(ProductVisibilityDefinition::VISIBILITY_LINK);
        $visibility->setProductId('product-id');

        $product->setVisibilities(new ProductVisibilityCollection([$visibility]));

        $repository = $this->createMock(SalesChannelRepository::class);
        $repository->method('search')->willReturn(
            new EntitySearchResult(
                'product',
                1,
                new ProductCollection([$product]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn($salesChannelId);
        $context->method('getSalesChannel')->willReturn(new SalesChannelEntity());

        $loader = new LinkedProductLoader($repository);

        static::assertSame($product, $loader->loadById('product-id', $context));
    }
}
