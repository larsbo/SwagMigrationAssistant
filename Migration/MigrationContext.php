<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Struct\Struct;

class MigrationContext extends Struct
{
    /**
     * @var string
     */
    private $profile;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $gateway;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var string
     */
    private $catalogId;

    /**
     * @var string
     */
    private $salesChannelId;

    public function __construct(
        string $profile,
        string $gateway,
        string $entity,
        array $credentials,
        int $offset,
        int $limit,
        ?string $catalogId = null,
        ?string $salesChannelId = null
    ) {
        $this->profile = $profile;
        $this->gateway = $gateway;
        $this->entity = $entity;
        $this->credentials = $credentials;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->catalogId = $catalogId;
        $this->salesChannelId = $salesChannelId;
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getGateway(): string
    {
        return $this->gateway;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function getGatewayIdentifier(): string
    {
        return $this->getProfile() . $this->getGateway();
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getCatalogId(): ?string
    {
        return $this->catalogId;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }
}
