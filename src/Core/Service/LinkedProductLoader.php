<?php

namespace Node\\LinkedProduct\\Core\\Service;

use Shopware\\Core\\Content\\Product\\ProductEntity;
use Shopware\\Core\\Content\\Product\\ProductVisibilityDefinition;
use Shopware\\Core\\Framework\\DataAbstractionLayer\\Search\\Criteria;
use Shopware\\Core\\System\\SalesChannel\\Entity\\SalesChannelRepository;
use Shopware\\Core\\System\\SalesChannel\\SalesChannelContext;

class LinkedProductLoader
{
    private SalesChannelRepository $productRepository;

    public function __construct(SalesChannelRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function loadById(string $id, SalesChannelContext $context): ?ProductEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('cover.media');
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('prices');
        $criteria->addAssociation('visibilities');

        $product = $this->productRepository->search($criteria, $context)->first();

        if (!$product instanceof ProductEntity) {
            return null;
        }

        if (!$product->getActive()) {
            return null;
        }

        $salesChannelId = $context->getSalesChannelId();
        $visibilities = $product->getVisibilities();
        if ($visibilities === null) {
            return null;
        }

        foreach ($visibilities as $visibility) {
            if (
                $visibility->getSalesChannelId() === $salesChannelId
                && $visibility->getVisibility() >= ProductVisibilityDefinition::VISIBILITY_LINK
            ) {
                return $product;
            }
        }

        return null;
    }
}
