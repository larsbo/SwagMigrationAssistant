<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Migration\Profile;

use PHPUnit\Framework\TestCase;
use SwagMigrationAssistant\Exception\ProfileNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistryInterface;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Profile\Dummy\DummyProfile;
use Symfony\Component\HttpFoundation\Response;

class ProfileRegistryTest extends TestCase
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    protected function setUp(): void
    {
        $this->profileRegistry = new ProfileRegistry(new DummyCollection([new DummyProfile()]));
    }

    public function testGetProfileNotFound(): void
    {
        try {
            $connection = new SwagMigrationConnectionEntity();
            $connection->setProfileName('foo');
            $migrationContext = new MigrationContext($connection);

            $this->profileRegistry->getProfile($migrationContext);
        } catch (\Exception $e) {
            /* @var ProfileNotFoundException $e */
            static::assertInstanceOf(ProfileNotFoundException::class, $e);
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
        }
    }
}
