<?php

namespace Node\LinkedProduct\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration202511090001LinkedProductCustomField extends MigrationStep
{
    public const FIELD_SET_NAME = 'node_linked_product';
    public const FIELD_NAME = 'node_linked_product_id';

    public function getCreationTimestamp(): int
    {
        return 202511090001;
    }

    public function update(Connection $connection): void
    {
        $setId = $connection->fetchOne(
            'SELECT `id` FROM `custom_field_set` WHERE `name` = :name',
            ['name' => self::FIELD_SET_NAME]
        );

        if (!is_string($setId)) {
            $setId = Uuid::randomHex();
            $connection->insert('custom_field_set', [
                'id' => Uuid::fromHexToBytes($setId),
                'name' => self::FIELD_SET_NAME,
                'config' => json_encode([
                    'label' => [
                        'en-GB' => 'Linked Product',
                        'de-DE' => 'Verknüpftes Produkt',
                    ],
                ], JSON_THROW_ON_ERROR),
                'active' => 1,
                'global' => 0,
                'position' => 1,
            ]);

            $connection->insert('custom_field_set_relation', [
                'id' => Uuid::fromHexToBytes(Uuid::randomHex()),
                'set_id' => Uuid::fromHexToBytes($setId),
                'entity_name' => 'product',
            ]);
        } else {
            $setId = Uuid::fromBytesToHex($setId);
        }

        $fieldId = $connection->fetchOne(
            'SELECT `id` FROM `custom_field` WHERE `name` = :name',
            ['name' => self::FIELD_NAME]
        );

        if (!is_string($fieldId)) {
            $fieldId = Uuid::randomHex();
            $connection->insert('custom_field', [
                'id' => Uuid::fromHexToBytes($fieldId),
                'name' => self::FIELD_NAME,
                'type' => 'text',
                'config' => json_encode([
                    'type' => 'text',
                    'label' => [
                        'en-GB' => 'Linked product ID',
                        'de-DE' => 'Verknüpfte Produkt-ID',
                    ],
                    'componentName' => 'sw-field',
                    'customFieldType' => 'text',
                ], JSON_THROW_ON_ERROR),
                'active' => 1,
                'set_id' => Uuid::fromHexToBytes($setId),
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // no destructive changes required
    }
}
