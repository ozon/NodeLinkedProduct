<?php

declare(strict_types=1);

namespace Node\LinkedProduct\Cms\Element;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Struct\Struct;

class LinkedProductCmsElement extends Struct
{
    protected ?ProductEntity $product = null;

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getApiAlias(): string
    {
        return 'node_linked_product_cms_element';
    }
}
