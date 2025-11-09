<?php

declare(strict_types=1);

namespace Node\LinkedProduct\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Migration\MigrationStep;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class Migration202511090001LinkedProductCustomField extends MigrationStep
{
    public const FIELD_SET_NAME = 'node_linked_product';
    public const FIELD_NAME = 'node_linked_product_id';

    public function getCreationTimestamp(): int
    {
        return 1731158400;
    }

    public function update(Connection $connection): void
    {
        $now = (new DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $fieldSetId = $this->ensureCustomFieldSet($connection, $now);
        $this->ensureCustomFieldSetRelation($connection, $fieldSetId);
        $this->ensureCustomField($connection, $fieldSetId, $now);
    }

    public function updateDestructive(Connection $connection): void
    {
        // no destructive changes required
    }

    private function ensureCustomFieldSet(Connection $connection, string $now): string
    {
        $existingId = $connection->fetchOne(
            'SELECT `id` FROM `custom_field_set` WHERE `name` = :name',
            ['name' => self::FIELD_SET_NAME]
        );

        if (is_string($existingId)) {
            return $existingId;
        }

        $fieldSetId = Uuid::randomBytes();

        $connection->insert('custom_field_set', [
            'id' => $fieldSetId,
            'name' => self::FIELD_SET_NAME,
            'config' => json_encode([
                'label' => [
                    'de-DE' => 'Verknüpftes Produkt',
                    'en-GB' => 'Linked Product',
                ],
            ], JSON_THROW_ON_ERROR),
            'active' => 1,
            'created_at' => $now,
        ]);

        return $fieldSetId;
    }

    private function ensureCustomFieldSetRelation(Connection $connection, string $fieldSetId): void
    {
        $relationExists = $connection->fetchOne(
            'SELECT 1 FROM `custom_field_set_relation` WHERE `set_id` = :setId AND `entity_name` = :entity LIMIT 1',
            ['setId' => $fieldSetId, 'entity' => 'product']
        );

        if (is_string($relationExists)) {
            return;
        }

        $connection->insert('custom_field_set_relation', [
            'id' => Uuid::randomBytes(),
            'set_id' => $fieldSetId,
            'entity_name' => 'product',
        ]);
    }

    private function ensureCustomField(Connection $connection, string $fieldSetId, string $now): void
    {
        $existingFieldId = $connection->fetchOne(
            'SELECT `id` FROM `custom_field` WHERE `name` = :name',
            ['name' => self::FIELD_NAME]
        );

        if (is_string($existingFieldId)) {
            return;
        }

        $connection->insert('custom_field', [
            'id' => Uuid::randomBytes(),
            'name' => self::FIELD_NAME,
            'type' => 'entity',
            'config' => json_encode([
                'label' => [
                    'de-DE' => 'Verknüpftes Produkt',
                    'en-GB' => 'Linked Product',
                ],
                'entity' => 'product',
                'componentName' => 'sw-entity-single-select',
                'customFieldPosition' => 1,
            ], JSON_THROW_ON_ERROR),
            'active' => 1,
            'set_id' => $fieldSetId,
            'created_at' => $now,
        ]);
    }
}
