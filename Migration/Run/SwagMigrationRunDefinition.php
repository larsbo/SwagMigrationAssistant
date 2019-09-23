<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionDefinition;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileDefinition;

class SwagMigrationRunDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_migration_run';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new FkField('connection_id', 'connectionId', SwagMigrationConnectionDefinition::class),
            new JsonField('environment_information', 'environmentInformation'),
            new JsonField('progress', 'progress'),
            new JsonField('premapping', 'premapping'),
            new StringField('user_id', 'userId'),
            new StringField('access_token', 'accessToken'),
            (new StringField('status', 'status'))->setFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField(),
            new ManyToOneAssociationField('connection', 'connection_id', SwagMigrationConnectionDefinition::class, 'id', true),
            new OneToManyAssociationField('data', SwagMigrationDataDefinition::class, 'run_id'),
            new OneToManyAssociationField('mediaFiles', SwagMigrationMediaFileDefinition::class, 'run_id'),
            new OneToManyAssociationField('logs', SwagMigrationLoggingDefinition::class, 'run_id'),
        ]);
    }

    public function getCollectionClass(): string
    {
        return SwagMigrationRunCollection::class;
    }

    public function getEntityClass(): string
    {
        return SwagMigrationRunEntity::class;
    }
}
