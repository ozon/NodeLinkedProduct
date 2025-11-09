<?php

namespace Node\\LinkedProduct;

use Doctrine\\DBAL\\Connection;
use Shopware\\Core\\Framework\\Plugin;
use Shopware\\Core\\Framework\\Plugin\\Context\\UninstallContext;

class NodeLinkedProduct extends Plugin
{
    public const CUSTOM_FIELD_SET_NAME = 'node_linked_product';
    public const CUSTOM_FIELD_NAME = 'node_linked_product_id';

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        $container = $this->container;
        if (!$container || !$container->has(Connection::class)) {
            return;
        }

        /** @var Connection $connection */
        $connection = $container->get(Connection::class);

        $setId = $connection->fetchOne(
            'SELECT `id` FROM `custom_field_set` WHERE `name` = :name',
            ['name' => self::CUSTOM_FIELD_SET_NAME]
        );

        if (!is_string($setId)) {
            return;
        }

        $connection->executeStatement(
            'DELETE FROM `custom_field_set_relation` WHERE `set_id` = :id',
            ['id' => $setId]
        );

        $connection->executeStatement(
            'DELETE FROM `custom_field_set_translation` WHERE `custom_field_set_id` = :id',
            ['id' => $setId]
        );

        $connection->executeStatement(
            'DELETE FROM `custom_field` WHERE `name` = :name',
            ['name' => self::CUSTOM_FIELD_NAME]
        );

        $connection->executeStatement(
            'DELETE FROM `custom_field_set` WHERE `id` = :id',
            ['id' => $setId]
        );
    }
}
