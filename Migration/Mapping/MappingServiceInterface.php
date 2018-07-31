<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\Context;

interface MappingServiceInterface
{
    public function getUuid(string $entityName, string $oldId, Context $context): ?string;

    public function createNewUuid(string $profile, string $entityName, string $oldId, Context $context, array $additionalData = null): string;

    public function getLanguageUuid(string $profile, string $localeCode, Context $context): array;
}
