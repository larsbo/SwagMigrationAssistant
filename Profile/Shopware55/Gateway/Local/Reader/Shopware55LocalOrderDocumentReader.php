<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class Shopware55LocalOrderDocumentReader extends Shopware55LocalAbstractReader implements LocalReaderInterface
{
    public function supports(string $profileName, DataSet $dataSet): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME && $dataSet::getEntity() === DefaultEntities::ORDER_DOCUMENTS;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);
        $documents = $this->mapData($this->fetchDocuments(), [], ['document']);

        $locale = $this->getDefaultShopLocale();

        foreach ($documents as &$document) {
            $document['_locale'] = str_replace('_', '-', $locale);
        }

        return $documents;
    }

    private function fetchDocuments(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_order_documents', 'document');
        $this->addTableSelection($query, 's_order_documents', 'document');

        $query->leftJoin('document', 's_order_documents_attributes', 'attributes', 'document.id = attributes.documentID');
        $this->addTableSelection($query, 's_order_documents_attributes', 'attributes');

        $query->leftJoin('document', 's_core_documents', 'document_documenttype', 'document.type = document_documenttype.id');
        $this->addTableSelection($query, 's_core_documents', 'document_documenttype');

        return $query->execute()->fetchAll();
    }
}
