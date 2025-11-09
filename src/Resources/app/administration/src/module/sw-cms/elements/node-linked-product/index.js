import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'node-linked-product',
    label: 'node.linked_product.cmsLabel', // Snippet-Schlüssel
    component: 'sw-cms-el-node-linked-product',
    configComponent: 'sw-cms-el-config-node-linked-product',
    previewComponent: 'sw-cms-el-preview-node-linked-product',
    defaultConfig: {
        product: {
            source: 'static',
            value: null,
            required: true,
        }
    }
});
