import template from './sw-cms-el-config-node-linked-product.html.twig';

Shopware.Component.register('sw-cms-el-config-node-linked-product', {
    template,
    mixins: [Shopware.Mixin.get('cms-element')],
    inject: ['repositoryFactory'],

    computed: {
        productRepository() {
            return this.repositoryFactory.create('product');
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('node-linked-product');

            // Sicherstellen, dass die Konfigurationsstruktur existiert
            if (this.element.config.product.value === undefined) {
                this.element.config.product.value = null;
            }
        }
    }
});
