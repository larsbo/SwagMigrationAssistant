<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Converter;

use SwagMigrationNext\Exception\ConverterNotFoundException;
use SwagMigrationNext\Migration\MigrationContextInterface;

class ConverterRegistry implements ConverterRegistryInterface
{
    /**
     * @var ConverterInterface[]
     */
    private $converters;

    public function __construct(iterable $converters)
    {
        $this->converters = $converters;
    }

    /**
     * @throws ConverterNotFoundException
     */
    public function getConverter(MigrationContextInterface $context): ConverterInterface
    {
        $profileName = $context->getProfileName();
        foreach ($this->converters as $converter) {
            if ($converter->supports($profileName, $context->getEntity())) {
                return $converter;
            }
        }

        throw new ConverterNotFoundException($profileName);
    }
}
