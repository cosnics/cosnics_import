<?php
namespace Chamilo\Libraries\Storage\Repository;

use Chamilo\Libraries\Storage\Architecture\Interfaces\StorageUnitDatabaseInterface;

/**
 * @package Chamilo\Libraries\Storage\Repository
 * @author  Hans De Bisschop <hans.de.bisschop@ehb.be>
 * @author  Magali Gillard <magali.gillard@ehb.be>
 */
class StorageUnitRepository
{
    public const ALTER_STORAGE_UNIT_ADD = 1;
    public const ALTER_STORAGE_UNIT_ADD_INDEX = 7;
    public const ALTER_STORAGE_UNIT_ADD_PRIMARY_KEY = 5;
    public const ALTER_STORAGE_UNIT_ADD_UNIQUE = 8;
    public const ALTER_STORAGE_UNIT_CHANGE = 2;
    public const ALTER_STORAGE_UNIT_DROP = 3;
    public const ALTER_STORAGE_UNIT_DROP_INDEX = 6;
    public const ALTER_STORAGE_UNIT_DROP_PRIMARY_KEY = 4;

    private StorageUnitDatabaseInterface $storageUnitDatabase;

    public function __construct(StorageUnitDatabaseInterface $storageUnitDatabase)
    {
        $this->storageUnitDatabase = $storageUnitDatabase;
    }

    /**
     * @param string[][]|string[] $attributes
     */
    public function alter(int $type, string $storageUnitName, string $property, array $attributes = []): bool
    {
        return $this->getStorageUnitDatabase()->alter($type, $storageUnitName, $property, $attributes);
    }

    public function alterIndex(int $type, string $storageUnitName, ?string $indexName = null, array $columns = []): bool
    {
        return $this->getStorageUnitDatabase()->alterIndex($type, $storageUnitName, $indexName, $columns);
    }

    /**
     * @param string[][] $properties
     * @param string[][][] $indexes
     */
    public function create(string $storageUnitName, array $properties = [], array $indexes = []): bool
    {
        return $this->getStorageUnitDatabase()->create($storageUnitName, $properties, $indexes);
    }

    public function drop(string $storageUnitName): bool
    {
        return $this->getStorageUnitDatabase()->drop($storageUnitName);
    }

    public function exists(string $storageUnitName): bool
    {
        return $this->getStorageUnitDatabase()->exists($storageUnitName);
    }

    public function getStorageUnitDatabase(): StorageUnitDatabaseInterface
    {
        return $this->storageUnitDatabase;
    }

    public function initializeStorage(string $databaseName, bool $overwriteIfDatabaseAlreadyExists): bool
    {
        return $this->getStorageUnitDatabase()->initializeStorage($databaseName, $overwriteIfDatabaseAlreadyExists);
    }

    public function optimize(string $storageUnitName): bool
    {
        return $this->getStorageUnitDatabase()->optimize($storageUnitName);
    }

    public function rename(string $oldStorageUnitName, string $newStorageUnitName): bool
    {
        return $this->getStorageUnitDatabase()->rename($oldStorageUnitName, $newStorageUnitName);
    }

    public function setStorageUnitDatabase(StorageUnitDatabaseInterface $storageUnitDatabase): StorageUnitRepository
    {
        $this->storageUnitDatabase = $storageUnitDatabase;

        return $this;
    }

    public function truncate(string $storageUnitName, ?bool $optimize = true): bool
    {
        if (!$this->getStorageUnitDatabase()->truncate($storageUnitName))
        {
            return false;
        }

        if ($optimize && !$this->optimize($storageUnitName))
        {
            return false;
        }

        return true;
    }
}