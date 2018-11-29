<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Struct\Struct;

class ProgressState extends Struct
{
    public const STATUS_FETCH_DATA = 0;
    public const STATUS_WRITE_DATA = 1;
    public const STATUS_DOWNLOAD_DATA = 2;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var array
     */
    protected $profile;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var bool
     */
    protected $migrationRunning;

    /**
     * @var bool
     */
    protected $validMigrationRunToken;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var array
     */
    protected $entityGroups;

    /**
     * @var int
     */
    protected $entityCount;

    /**
     * @var int
     */
    protected $finishedCount;

    public function __construct(
        bool $isMigrationRunning,
        bool $validMigrationRunToken,
        string $runId = null,
        array $profile = null,
        int $status = -1,
        string $entity = null,
        int $finishedCount = null,
        int $entityCount = null
    ) {
        $this->migrationRunning = $isMigrationRunning;
        $this->validMigrationRunToken = $validMigrationRunToken;
        $this->runId = $runId;
        $this->profile = $profile;
        $this->status = $status;
        $this->entity = $entity;
        $this->finishedCount = $finishedCount;
        $this->entityCount = $entityCount;
    }

    public function isMigrationRunning(): bool
    {
        return $this->migrationRunning;
    }

    public function isMigrationRunTokenValid(): bool
    {
        return $this->validMigrationRunToken;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getProfile(): array
    {
        return $this->profile;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getFinishedCount(): int
    {
        return $this->finishedCount;
    }

    public function getEntityCount(): int
    {
        return $this->entityCount;
    }

    public function getEntityGroups(): array
    {
        return $this->entityGroups;
    }

    public function setEntityGroups(array $entityGroups): void
    {
        $this->entityGroups = $entityGroups;
    }
}
